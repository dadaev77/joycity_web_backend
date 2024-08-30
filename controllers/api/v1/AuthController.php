<?php

namespace app\controllers\api\v1;

use app\components\ApiAuth;
use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\V1Controller;
use app\helpers\POSTHelper;
use app\models\DeliveryPointAddress;
use app\models\TypeDeliveryPoint;
use app\models\User;
use app\models\UserSettings;
use app\services\EmailService;
use app\services\output\ProfileOutputService;
use app\services\twilio\TwilioService;
use app\services\UserActionLogService as LogService;
use Exception as BaseException;
use Throwable;
use Yii;

class AuthController extends V1Controller implements ApiAuth
{
    private const PASSWORD_RESET_CODE_KEY = 'password_reset_code_<CODE>';
    private const EMAIL_UPDATE_CODE_KEY = 'email_update_code_<CODE>';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['login'] = ['post'];
        $behaviors['verbFilter']['actions']['logout'] = ['post'];
        $behaviors['verbFilter']['actions']['register'] = ['post'];
        $behaviors['verbFilter']['actions']['email-check'] = ['post'];
        $behaviors['verbFilter']['actions']['update-email-step-1'] = ['put'];
        $behaviors['verbFilter']['actions']['update-email-step-2'] = ['put'];
        $behaviors['verbFilter']['actions']['change-password'] = ['put'];
        $behaviors['verbFilter']['actions']['twilio-token'] = ['get'];
        $behaviors['authenticator']['except'] = [
            'login',
            'email-check',
            'register',
            'reset-password-step-1',
            'reset-password-step-2',
            'reset-password-step-3',
        ];

        return $behaviors;
    }

    public function actionLogin()
    {
        $params = POSTHelper::getPostWithKeys(
            ['role', 'password', 'phone_number', 'email'],
            true,
        );

        $notValidParams = POSTHelper::getEmptyParams($params, true);

        if (
            (!$params['phone_number'] && !$params['email']) ||
            !$params['password'] ||
            !$params['role']
        ) {
            $errors = array_map(
                static fn($idx) => "Param `$notValidParams[$idx]` is empty",
                array_flip($notValidParams),
            );

            return ApiResponse::codeErrors(
                User::apiCodes()->BAD_REQUEST,
                $errors,
            );
        }

        $params['email'] = strtolower($params['email'] ?? '');

        $user = User::find()
            ->where(
                $params['phone_number']
                    ? ['phone_number' => $params['phone_number']]
                    : ['email' => $params['email']],
            )
            ->one();

        if (!$user) {
            return ApiResponse::code(User::apiCodes()->CREDENTIALS_NOT_PASSED);
        }
        if ($user->role !== $params['role']) {
            return ApiResponse::code(
                User::apiCodes()->CREDENTIALS_NOT_PASSED_FOR_THIS_ROLE,
            );
        }

        if (
            !Yii::$app
                ->getSecurity()
                ->validatePassword($params['password'], $user->password)
        ) {
            return ApiResponse::code(User::apiCodes()->CREDENTIALS_NOT_PASSED);
        }

        return ApiResponse::code(null, [
            'access_token' => $user->access_token,
        ]);
    }

    public function actionLogout()
    {
        $user = User::getIdentity();
        LogService::log('logout is called by ' . $user->email);
        $user->access_token =
            Yii::$app->security->generateRandomString() .
            Yii::$app->security->generateRandomString();
        $user->save(false);

        return ApiResponse::code(ResponseCodes::getSelf()->SUCCESS);
    }

    public function actionEmailCheck()
    {
        $apiCodes = User::apiCodes();
        $request = Yii::$app->request;
        $user = User::isset(
            ['email' => strtolower($request->post('email'))],
            true,
        );

        if ($user) {
            LogService::log('email check is called by ' . $user->email);
            return ApiResponse::code($apiCodes->EMAIL_EXISTS);
        }

        return ApiResponse::code($apiCodes->SUCCESS);
    }

    public function actionRegister()
    {
        $request = Yii::$app->request;
        $apiCodes = User::apiCodes();
        LogService::log('register is called by ' . $request->post('email'));
        try {
            $transaction = Yii::$app->db->beginTransaction();
            $email = strtolower($request->post('email'));
            $password = $request->post('password');
            $confirmPassword = $request->post('confirm_password');
            $phoneNumber = $request->post('phone_number');
            $organizationName = $request->post('organization_name');
            $surname = $request->post('surname');
            $name = $request->post('name');
            $role = $request->post('role');
            $phone_country_code = $request->post('phone_country_code');
            $country = $request->post('country');
            $city = $request->post('city');
            $address = $request->post('address');

            $user = new User([
                'email' => $email,
                'password' => $password,
                'phone_number' => $phoneNumber,
                'organization_name' => $organizationName,
                'surname' => $surname,
                'name' => $name,
                'role' => $role,
                'rating' => Yii::$app->params['baseRating'],
                'phone_country_code' => $phone_country_code,
            ]);

            $requiredAttributes = [
                'email',
                'password',
                'phone_number',
                'organization_name',
                'surname',
                'name',
                'role',
                'phone_country_code',
                'rating',
            ];

            if (
                $role === User::ROLE_BUYER ||
                $role === User::ROLE_FULFILLMENT
            ) {
                $requiredAttributes = array_merge($requiredAttributes, [
                    'country',
                    'city',
                    'address',
                ]);
                $user->country = $country;
                $user->city = $city;
                $user->address = $address;
            }

            if ($password !== $confirmPassword) {
                return ApiResponse::code(
                    $apiCodes->CONFIRM_PASSWORD_DOESNT_MATCH,
                );
            }

            if (
                !in_array(
                    $role,
                    [
                        User::ROLE_BUYER,
                        User::ROLE_CLIENT,
                        User::ROLE_FULFILLMENT,
                    ],
                    true,
                )
            ) {
                return ApiResponse::codeErrors($apiCodes->NOT_VALID, [
                    'role' =>
                    'Param `role` can only be "client" or "buyer" or "fulfillment"',
                ]);
            }

            if (!$user->validate($requiredAttributes)) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $user->getFirstErrors(),
                );
            }
            LogService::success('created user ' . $request->post('email'));
            $user->personal_id = md5(time() . random_int(1e3, 9e3));
            $user->password = Yii::$app
                ->getSecurity()
                ->generatePasswordHash($user->password);
            $user->access_token =
                Yii::$app->security->generateRandomString() .
                Yii::$app->security->generateRandomString();

            if (!$user->save(false)) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $user->getFirstErrors(),
                );
            }

            if ($role === User::ROLE_FULFILLMENT) {
                $deliveryPointAddress = new DeliveryPointAddress([
                    'type_delivery_point_id' =>
                    TypeDeliveryPoint::TYPE_FULFILLMENT,
                    'address' => $address,
                    'user_id' => $user->id,
                ]);

                if (!$deliveryPointAddress->save()) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $deliveryPointAddress->getFirstErrors(),
                    );
                }
            }

            $settings = new UserSettings();
            $settings->user_id = $user->id;
            $settings->currency =
                $role === User::ROLE_CLIENT || $role === User::ROLE_FULFILLMENT
                ? UserSettings::CURRENCY_RUB
                : UserSettings::CURRENCY_CNY;
            $settings->application_language =
                UserSettings::APPLICATION_LANGUAGE_RU;
            $settings->chat_language = UserSettings::CHAT_LANGUAGE_RU;

            if (!$settings->save()) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $settings->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::code($apiCodes->SUCCESS, [
                'access_token' => $user->access_token,
            ]);
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    public function actionUpdateEmailStep1()
    {
        $apiCodes = User::apiCodes();

        try {
            $user = User::getIdentity();
            $email = strtolower(Yii::$app->request->post('email'));
            $isset = User::isset(['email' => $email]);
            $userModel = new User(['email' => $email]);

            if ($isset) {
                return ApiResponse::code($apiCodes->EMAIL_EXISTS);
            }

            if (!$userModel->validate(['email'])) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $userModel->getFirstErrors(),
                );
            }

            if ($user->email === $email && $user->is_email_confirmed) {
                return ApiResponse::codeErrors($apiCodes->BAD_REQUEST, [
                    'email' => 'Ваш адрес электронной почты уже подтвержден.',
                ]);
            }

            $code = random_int(1000, 9999);

            Yii::$app->cache->set(
                str_replace('<CODE>', self::EMAIL_UPDATE_CODE_KEY, $code),
                ['user_id' => $user->id, 'email' => $email],
                300,
            );

            $to = $user->email;
            $subject = 'Подтверждение почты';
            $message = 'Код для подтверждения почты: ' . $code;
            $result = EmailService::sendEmail($to, $subject, $message);

            if (!$result) {
                return ApiResponse::code($apiCodes->ERROR_EMAIL_SEND);
            }

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (BaseException | Throwable) {
            return ApiResponse::code($apiCodes->INTERNAL_ERROR);
        }
    }

    public function actionUpdateEmailStep2()
    {
        $apiCodes = User::apiCodes();

        try {
            $user = User::getIdentity();
            $code = Yii::$app->request->post('code');
            $cacheKey = str_replace(
                '<CODE>',
                self::EMAIL_UPDATE_CODE_KEY,
                $code,
            );
            $storedCodeInfo = Yii::$app->cache->get($cacheKey);

            if (!$storedCodeInfo || $storedCodeInfo['user_id'] !== $user->id) {
                return ApiResponse::code(
                    $apiCodes->WRONG_EMAIL_VERIFICATION_CODE,
                );
            }

            Yii::$app->cache->delete($cacheKey);

            $user->is_email_confirmed = 1;
            $user->email = $storedCodeInfo['email'];
            $user->save(false);

            return ApiResponse::codeInfo(
                $apiCodes->SUCCESS,
                ProfileOutputService::getEntity($user->id),
            );
        } catch (BaseException | Throwable) {
            return ApiResponse::code($apiCodes->INTERNAL_ERROR);
        }
    }

    public function actionResetPasswordStep1()
    {
        $apiCodes = User::apiCodes();

        try {
            $email = strtolower(Yii::$app->request->post('email'));
            $isset = User::isset(['email' => $email]);

            if (!$isset) {
                return ApiResponse::code($apiCodes->EMAIL_NOT_EXISTS);
            }

            $code = random_int(1000, 9999);

            Yii::$app->cache->set(
                str_replace('<CODE>', self::PASSWORD_RESET_CODE_KEY, $code),
                ['email' => $email],
                900,
            );

            $to = $email;
            $subject = 'Забыл пароль';
            $message = 'Код для смены пароля: ' . $code;
            $result = EmailService::sendEmail($to, $subject, $message);

            if (!$result) {
                return ApiResponse::code($apiCodes->ERROR_EMAIL_SEND);
            }

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (BaseException | Throwable) {
            return ApiResponse::code($apiCodes->INTERNAL_ERROR);
        }
    }

    public function actionResetPasswordStep2()
    {
        $apiCodes = User::apiCodes();

        try {
            $code = Yii::$app->request->post('code');
            $email = strtolower(Yii::$app->request->post('email'));
            $cacheKey = str_replace(
                '<CODE>',
                self::PASSWORD_RESET_CODE_KEY,
                $code,
            );
            $storedCodeInfo = Yii::$app->cache->get($cacheKey);

            if (!$storedCodeInfo || $storedCodeInfo['email'] !== $email) {
                return ApiResponse::code(
                    $apiCodes->WRONG_PASSWORD_RESET_VERIFICATION_CODE,
                );
            }

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (BaseException | Throwable) {
            return ApiResponse::code($apiCodes->INTERNAL_ERROR);
        }
    }

    public function actionResetPasswordStep3()
    {
        $apiCodes = User::apiCodes();

        try {
            $code = Yii::$app->request->post('code');
            $email = strtolower(Yii::$app->request->post('email'));
            $newPassword = Yii::$app->request->post('new_password');
            $confirmPassword = Yii::$app->request->post('confirm_password');
            $cacheKey = str_replace(
                '<CODE>',
                self::PASSWORD_RESET_CODE_KEY,
                $code,
            );
            $storedCodeInfo = Yii::$app->cache->get($cacheKey);

            if ($newPassword !== $confirmPassword) {
                return ApiResponse::code(
                    $apiCodes->CONFIRM_PASSWORD_DOESNT_MATCH,
                );
            }

            if (!$storedCodeInfo || $storedCodeInfo['email'] !== $email) {
                return ApiResponse::code(
                    $apiCodes->WRONG_PASSWORD_RESET_VERIFICATION_CODE,
                );
            }

            $user = User::findOne(['email' => $email]);

            if (!$user) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            $user->password = $newPassword;
            $user->access_token =
                Yii::$app->security->generateRandomString() .
                Yii::$app->security->generateRandomString();

            if (!$user->validate(['password'])) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $user->getFirstErrors(),
                );
            }

            $user->password = Yii::$app->security->generatePasswordHash(
                $newPassword,
            );

            if (!$user->save(false)) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $user->getFirstErrors(),
                );
            }

            Yii::$app->cache->delete($cacheKey);

            return ApiResponse::code($apiCodes->SUCCESS, [
                'access_token' => $user->access_token,
            ]);
        } catch (BaseException | Throwable) {
            return ApiResponse::code($apiCodes->INTERNAL_ERROR);
        }
    }

    public function actionUpdatePassword()
    {
        $apiCodes = User::apiCodes();

        try {
            $user = User::getIdentity();
            $oldPassword = Yii::$app->request->post('old_password');
            $newPassword = Yii::$app->request->post('new_password');
            $confirmPassword = Yii::$app->request->post('confirm_password');

            if (
                !Yii::$app->security->validatePassword(
                    $oldPassword,
                    $user->password,
                )
            ) {
                return ApiResponse::code($apiCodes->OLD_PASSWORD_DOESNT_MATCH);
            }

            if ($newPassword !== $confirmPassword) {
                return ApiResponse::code(
                    $apiCodes->CONFIRM_PASSWORD_DOESNT_MATCH,
                );
            }

            $user->password = $newPassword;
            $user->access_token =
                Yii::$app->security->generateRandomString() .
                Yii::$app->security->generateRandomString();

            if (!$user->validate(['password'])) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $user->getFirstErrors(),
                );
            }

            $user->password = Yii::$app->security->generatePasswordHash(
                $newPassword,
            );

            if (!$user->save(false)) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $user->getFirstErrors(),
                );
            }

            return ApiResponse::code($apiCodes->SUCCESS, [
                'access_token' => $user->access_token,
            ]);
        } catch (BaseException | Throwable) {
            return ApiResponse::code($apiCodes->INTERNAL_ERROR);
        }
    }

    public function actionTwilioToken()
    {
        $user = User::getIdentity();
        LogService::log('twilio token is called by ' . $user->email);
        return ApiResponse::code(ResponseCodes::getStatic()->SUCCESS, [
            'access_token' => TwilioService::generateJWT($user->personal_id),
        ]);
    }
}
