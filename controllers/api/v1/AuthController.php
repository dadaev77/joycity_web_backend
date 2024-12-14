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
    public function init()
    {
        parent::init();
        LogService::setController('AuthController');
    }
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
    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Вход пользователя",
     *     description="Этот метод позволяет пользователю войти в систему, предоставив свои учетные данные.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role", "password"},
     *             @OA\Property(property="role", type="string", example="buyer"),
     *             @OA\Property(property="password", type="string", example="your_password"),
     *             @OA\Property(property="phone_number", type="string", example="1234567890"),
     *             @OA\Property(property="email", type="string", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный вход",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="your_access_token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации данных",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Неверные учетные данные")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера")
     *         )
     *     )
     * )
     */
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
            return ApiResponse::code(User::apiCodes()->CREDENTIALS_NOT_FOUND);
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Выход пользователя",
     *     description="Этот метод позволяет пользователю выйти из системы.",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный выход",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Вы успешно вышли")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/email-check",
     *     summary="Проверка существования email",
     *     description="Этот метод позволяет проверить, существует ли указанный email в системе.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email существует",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Email уже существует")
     *         )
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Email не существует",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Email доступен")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Регистрация пользователя",
     *     description="Этот метод позволяет пользователю зарегистрироваться в системе.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password", "confirm_password", "phone_number", "organization_name", "surname", "name", "role"},
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="your_password"),
     *             @OA\Property(property="confirm_password", type="string", example="your_password"),
     *             @OA\Property(property="phone_number", type="string", example="1234567890"),
     *             @OA\Property(property="organization_name", type="string", example="My Organization"),
     *             @OA\Property(property="surname", type="string", example="Иванов"),
     *             @OA\Property(property="name", type="string", example="Иван"),
     *             @OA\Property(property="role", type="string", example="buyer"),
     *             @OA\Property(property="phone_country_code", type="string", example="+1"),
     *             @OA\Property(property="country", type="string", example="Россия"),
     *             @OA\Property(property="city", type="string", example="Москва"),
     *             @OA\Property(property="address", type="string", example="Улица Пушкина, дом 1"),
     *             @OA\Property(property="telegram", type="string", example="@username")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Пользователь успешно зарегистрирован",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="access_token", type="string", example="your_access_token"),
     *             @OA\Property(property="uuid", type="string", example="AAA-999")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации данных",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="Некорректный email")),
     *                 @OA\Property(property="password", type="array", @OA\Items(type="string", example="Пароли не совпадают"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера")
     *         )
     *     )
     * )
     */
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
            $telegram = $request->post('telegram') ? ($request->post('telegram') != "" ? $request->post('telegram') : null) : null;

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
                'telegram' => $telegram,
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
                'telegram',
            ];

            if (
                $role === User::ROLE_BUYER ||
                $role === User::ROLE_BUYER_DEMO ||
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
                        User::ROLE_BUYER_DEMO, // demo buyer
                        User::ROLE_CLIENT,
                        User::ROLE_FULFILLMENT,
                        User::ROLE_CLIENT_DEMO, // demo client
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

            /**
             * generate uuid for user as AAA-999
             */
            function generateCustomUUID()
            {
                $letters = chr(random_int(65, 90)) . chr(random_int(65, 90)) . chr(random_int(65, 90));
                $numbers = random_int(100, 999);
                return $letters . '-' . $numbers;
            }

            $user->uuid = generateCustomUUID();

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
                'uuid' => $user->uuid,
            ]);
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/update-email-step-1",
     *     summary="Обновление email - шаг 1",
     *     description="Этот метод позволяет инициировать процесс обновления email, отправив код на текущий email пользователя.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="new_email@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Код для подтверждения email отправлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Код для подтверждения email отправлен")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации данных",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="Некорректный email"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/update-email-step-2",
     *     summary="Обновление email - шаг 2",
     *     description="Этот метод позволяет подтвердить новый email, используя код, отправленный на старый email.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="1234")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email успешно обновлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Email успешно обновлен")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный код",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Неверный код")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/reset-password-step-1",
     *     summary="Запрос на сброс пароля - шаг 1",
     *     description="Этот метод позволяет инициировать процесс сброса пароля, отправив код на указанный email.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Код для сброса пароля отправлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Код для сброса пароля отправлен на email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Email не существует",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Email не существует")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/reset-password-step-2",
     *     summary="Проверка кода сброса пароля - шаг 2",
     *     description="Этот метод позволяет проверить код, отправленный на email, для сброса пароля.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code", "email"},
     *             @OA\Property(property="code", type="string", example="1234"),
     *             @OA\Property(property="email", type="string", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Код подтвержден",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Код подтвержден")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный код",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Неверный код")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/reset-password-step-3",
     *     summary="Сброс пароля - шаг 3",
     *     description="Этот метод позволяет установить новый пароль после подтверждения кода.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code", "email", "new_password", "confirm_password"},
     *             @OA\Property(property="code", type="string", example="1234"),
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="new_password", type="string", example="new_password"),
     *             @OA\Property(property="confirm_password", type="string", example="new_password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Пароль успешно сброшен",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Пароль успешно сброшен")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации данных",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="new_password", type="array", @OA\Items(type="string", example="Пароль слишком короткий")),
     *                 @OA\Property(property="confirm_password", type="array", @OA\Items(type="string", example="Пароли не совпадают"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/update-password",
     *     summary="Обновление пароля",
     *     description="Этот метод позволяет пользователю обновить свой пароль.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"old_password", "new_password", "confirm_password"},
     *             @OA\Property(property="old_password", type="string", example="old_password"),
     *             @OA\Property(property="new_password", type="string", example="new_password"),
     *             @OA\Property(property="confirm_password", type="string", example="new_password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Пароль успешно обновлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Пароль успешно обновлен")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации данных",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="new_password", type="array", @OA\Items(type="string", example="Пароль слишком короткий")),
     *                 @OA\Property(property="confirm_password", type="array", @OA\Items(type="string", example="Пароли не совпадают"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/twilio-token",
     *     summary="Получение токена Twilio",
     *     description="Этот метод позволяет получить токен для работы с Twilio.",
     *     @OA\Response(
     *         response=200,
     *         description="Токен успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="access_token", type="string", example="your_twilio_access_token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Внутренняя ошибка сервера")
     *         )
     *     )
     * )
     */
    public function actionTwilioToken()
    {
        $user = User::getIdentity();
        LogService::log('twilio token is called by ' . $user->email);
        return ApiResponse::code(ResponseCodes::getStatic()->SUCCESS, [
            'access_token' => TwilioService::generateJWT($user->personal_id),
        ]);
    }
}
