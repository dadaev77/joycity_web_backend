<?php

namespace app\controllers\api\v1\internal;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\helpers\POSTHelper;
use app\models\User;
use app\models\UserSettings;
use app\services\EmailService;
use app\services\output\BuyerOutputService;
use app\services\output\ProfileOutputService;
use Throwable;
use Yii;


class UserController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['update'] = ['put'];
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['view'] = ['get'];
        $behaviors['verbFilter']['actions']['register'] = ['post'];

        return $behaviors;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/internal/user/register",
     *     summary="Регистрация нового пользователя",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password", "confirm_password", "phone_number", "surname", "name", "role"},
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="confirm_password", type="string", example="password123"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *             @OA\Property(property="surname", type="string", example="Doe"),
     *             @OA\Property(property="name", type="string", example="John"),
     *             @OA\Property(property="role", type="string", example="manager")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone_number", type="string"),
     *             @OA\Property(property="surname", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="is_verified", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function actionRegister()
    {
        $request = Yii::$app->request;
        $apiCodes = User::apiCodes();
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $email = strtolower($request->post('email'));
            $password = $request->post('password');
            $confirmPassword = $request->post('confirm_password');
            $phoneNumber = $request->post('phone_number');
            $surname = $request->post('surname');
            $name = $request->post('name');
            $role = $request->post('role');

            $user = new User([
                'email' => $email,
                'password' => $password,
                'phone_number' => $phoneNumber,
                'organization_name' => 'JoyCity',
                'surname' => $surname,
                'name' => $name,
                'role' => $role,
            ]);

            $requiredAttributes = [
                'email',
                'password',
                'phone_number',
                'organization_name',
                'surname',
                'name',
                'role',
            ];

            if ($password !== $confirmPassword) {
                $transaction?->rollBack();
                return ApiResponse::code(
                    $apiCodes->CONFIRM_PASSWORD_DOESNT_MATCH,
                );
            }

            if (
                !in_array(
                    $role,
                    [
                        User::ROLE_MANAGER,
                        User::ROLE_ADMIN,
                        User::ROLE_SUPER_ADMIN,
                    ],
                    true,
                )
            ) {
                $transaction?->rollBack();
                return ApiResponse::codeErrors($apiCodes->NOT_VALID, [
                    'role' => 'Param `role` can only be "manager" or "admin"',
                ]);
            }

            if (!$user->validate($requiredAttributes)) {
                $transaction?->rollBack();
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $user->getFirstErrors(),
                );
            }

            $user->personal_id = md5(time() . random_int(1e3, 9e3));
            $user->password = Yii::$app
                ->getSecurity()
                ->generatePasswordHash($user->password);
            $user->access_token =
                Yii::$app->security->generateRandomString() .
                Yii::$app->security->generateRandomString();

            if (!$user->save(false)) {
                $transaction?->rollBack();
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $user->getFirstErrors(),
                );
            }

            $to = $user->email;
            $subject = 'Данные для входа';
            $message = "Номер телефона $phoneNumber\nПароль: $password";
            $result = EmailService::sendEmail($to, $subject, $message);

            if (!$result) {
                $transaction?->rollBack();
                return ApiResponse::code($apiCodes->ERROR_EMAIL_SEND);
            }
            $settings = new UserSettings();
            $settings->user_id = $user->id;
            $settings->currency =
                $user->role === User::ROLE_MANAGER
                ? UserSettings::CURRENCY_CNY
                : UserSettings::CURRENCY_RUB;
            $settings->application_language =
                UserSettings::APPLICATION_LANGUAGE_RU;
            $settings->chat_language = UserSettings::CHAT_LANGUAGE_RU;

            if (!$settings->save()) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $settings->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                ProfileOutputService::getEntity($user->id),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/internal/user/update/{id}",
     *     summary="Обновить информацию о пользователе",
     *     tags={"User"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID пользователя",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John"),
     *             @OA\Property(property="surname", type="string", example="Doe"),
     *             @OA\Property(property="phone_number", type="string", example="+1234567890"),
     *             @OA\Property(property="organization_name", type="string", example="Company Inc."),
     *             @OA\Property(property="role", type="string", example="manager"),
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="is_deleted", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Пользователь успешно обновлен"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Пользователь не найден"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации параметров"
     *     )
     * )
     */
    public function actionUpdate($id)
    {
        $apiCodes = User::apiCodes();
        $user = User::findOne(
            [
                'id' => $id,
                'role' => [
                    User::ROLE_MANAGER,
                    User::ROLE_ADMIN,
                    User::ROLE_SUPER_ADMIN,
                ],
            ],
            true,
        );

        $postParams = POSTHelper::getPostWithKeys([
            'name',
            'surname',
            'phone_number',
            'organization_name',
            'role',
            'email',
            'is_deleted',
        ]);

        if (isset($postParams['email'])) {
            $postParams['email'] = strtolower($postParams['email']);
        }

        if (!$user) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        if (
            $user->role === User::ROLE_SUPER_ADMIN &&
            isset($postParams['is_deleted'])
        ) {
            unset($postParams['is_deleted']);
        }

        $user->load($postParams, '');
        if (!$user->validate(array_keys($postParams))) {
            return ApiResponse::codeErrors(
                $apiCodes->NOT_VALID,
                $user->getFirstErrors(),
            );
        }

        $existingUser = User::findOne(['phone_number' => $user->phone_number]);
        if ($existingUser && $existingUser->id !== $user->id) {
            return ApiResponse::code($apiCodes->PHONE_NUMBER_EXISTS);
        }

        $newPassword = Yii::$app->request->post('new_password');
        $confirmPassword = Yii::$app->request->post('confirm_password');

        if ($newPassword) {
            if ($newPassword !== $confirmPassword && $user->password) {
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
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (!$user->save(false)) {
                $transaction?->rollBack();
                return ApiResponse::code(
                    $apiCodes->NOT_VALID,
                    $user->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                ProfileOutputService::getEntity($user->id, true),
            );
        } catch (Throwable $e) {
            $transaction?->rollBack();
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/user",
     *     summary="Получить список пользователей",
     *     tags={"User"},
     *     @OA\Parameter(
     *         name="is_deleted",
     *         in="query",
     *         required=false,
     *         description="Флаг удаления пользователя",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         required=false,
     *         description="Роль пользователя",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         required=false,
     *         description="Email пользователя",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список пользователей успешно получен"
     *     )
     * )
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $query = User::find()->showWithDeleted();

        if ($is_deleted = $request->get('is_deleted')) {
            $query->andWhere(['is_deleted' => $is_deleted]);
        }

        if ($role = $request->get('role')) {
            $query->andWhere(['role' => $role]);
        }

        if ($email = $request->get('email')) {
            $query->andWhere(['email' => $email]);
        }

        return ApiResponse::info(
            ProfileOutputService::getCollection($query->column(), true),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/internal/user/view/{id}",
     *     summary="Просмотр информации о пользователе",
     *     tags={"User"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID пользователя",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Пользователь успешно найден"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Пользователь не найден"
     *     )
     * )
     */
    public function actionView(int $id)
    {
        $apiCodes = User::apiCodes();
        $user = User::findOne(['id' => $id], true);

        if (!$user) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        if ($user->role === User::ROLE_BUYER) {
            return ApiResponse::info(BuyerOutputService::getEntity($id, true));
        }

        return ApiResponse::info(ProfileOutputService::getEntity($id, true));
    }
}
