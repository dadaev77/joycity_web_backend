<?php

namespace app\controllers\api\v1\manager;

use app\controllers\api\v1\ManagerController;
use app\models\User;
use app\components\ApiResponse;
use app\helpers\POSTHelper;
use app\models\Order;
use app\services\AttachmentService;
use app\services\output\ProfileOutputService;
use Throwable;
use Yii;
use yii\db\Exception;
use yii\web\UploadedFile;
use app\services\push\PushService;

class ProfileController extends ManagerController
{
    //
    public function behaviors()
    {
        $behaviours = parent::behaviors();
        $behaviours['verbFilter']['actions']['update'] = ['put'];
        $behaviours['verbFilter']['actions']['upload-avatar'] = ['post'];
        $behaviours['verbFilter']['actions']['self'] = ['get'];
        $behaviours['verbFilter']['actions']['delete'] = ['delete'];

        // array_unshift($behaviours['access']['rules'], [
        //     'actions' => ['update', 'delete'],
        //     'allow' => false,
        //     'matchCallback' => fn() => User::getIdentity()->role === User::ROLE_CLIENT_DEMO,
        // ]);
        // $behaviours['access']['denyCallback'] = static function () {
        //     $response =
        //         User::getIdentity()->role === User::ROLE_CLIENT_DEMO ?
        //         ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
        //         false;
        //     Yii::$app->response->data = $response;
        // };
        return $behaviours;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/manager/profile/self",
     *     security={{"Bearer":{}}},
     *     summary="Получить информацию о текущем пользователе",
     *     description="Возвращает информацию о текущем пользователе.",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован"
     *     )
     * )
     */
    public function actionSelf()
    {
        $userId = Yii::$app->user->identity->id;
        $apiCodes = User::apiCodes();
        $isset = User::isset(['id' => $userId]);

        if (!$isset) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND);
        }

        return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
            'info' => ProfileOutputService::getEntity($userId),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/manager/profile/upload-avatar",
     *     security={{"Bearer":{}}},
     *     summary="Загрузить аватар пользователя",
     *     description="Позволяет пользователю загрузить аватар.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="images", type="array", @OA\Items(type="string", format="binary"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера"
     *     )
     * )
     */
    public function actionUploadAvatar()
    {
        $apiCodes = User::apiCodes();
        $user = Yii::$app->user->identity;
        $images = UploadedFile::getInstancesByName('images');
        $transaction = Yii::$app->db->beginTransaction();

        if (!$images) {
            $transaction?->rollBack();
            return ApiResponse::byResponseCode($apiCodes->BAD_REQUEST, [
                'errors' => ['images' => 'Вы не выбрали картинку'],
            ]);
        }

        try {
            $attachmentSaveResponse = AttachmentService::writeFilesCollection(
                $images,
                1,
                0,
            );

            if (!$attachmentSaveResponse->success) {
                $transaction?->rollBack();

                return ApiResponse::byResponseCode($apiCodes->INTERNAL_ERROR, [
                    'errors' => ['images' => 'Ошибка сохранения'],
                ]);
            }
            $user->avatar_id = $attachmentSaveResponse->result[0]->id;
            $user->save(false);

            $transaction?->commit();

            return ApiResponse::byResponseCode(null, [
                'info' => ProfileOutputService::getEntity($user->id),
            ]);
        } catch (Exception $e) {
            $transaction?->rollBack();

            return ApiResponse::byResponseCode($apiCodes->INTERNAL_ERROR);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/manager/profile/update",
     *     security={{"Bearer":{}}},
     *     summary="Обновить информацию о пользователе",
     *     description="Обновляет информацию о текущем пользователе.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="surname", type="string"),
     *             @OA\Property(property="phone_number", type="string"),
     *             @OA\Property(property="organization_name", type="string"),
     *             @OA\Property(property="phone_country_code", type="string"),
     *             @OA\Property(property="telegram", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера"
     *     )
     * )
     */
    public function actionUpdate()
    {
        $apiCodes = User::apiCodes();
        $user = Yii::$app->user->identity;
        $postParams = POSTHelper::getPostWithKeys([
            'name',
            'surname',
            'phone_number',
            'organization_name',
            'phone_country_code',
            'telegram',
            'currency',
        ]);

        $user->load($postParams, '');
        $settings = $user->userSettings;
        $settings->currency = $postParams['currency'];
        $settings->save(false);
        if (isset($postParams['phone_number'])) {
            $existingUser = User::isset([
                'phone_number' => $user->phone_number,
                'phone_country_code' => $user->phone_country_code,
            ]);
            if ($existingUser) {
                return ApiResponse::code($apiCodes->PHONE_NUMBER_EXISTS);
            }
            if (isset($postParams['phone_country_code'])) {
                $user->phone_country_code = $postParams['phone_country_code'];
            }
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (!$user->save(true, array_keys($postParams))) {
                $transaction?->rollBack();
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $user->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                ProfileOutputService::getEntity($user->id),
            );
        } catch (Throwable $e) {
            $transaction?->rollBack();
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/manager/profile/delete",
     *     security={{"Bearer":{}}},
     *     summary="Удалить пользователя",
     *     description="Удаляет текущего пользователя.",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера"
     *     )
     * )
     */
    public function actionDelete()
    {
        $user = Yii::$app->user->identity;
        $apiCodes = User::apiCodes();

        $hasForbiddenOrders = Order::find()
            ->where(['created_by' => $user->id])
            ->andWhere([
                'NOT IN',
                'status',
                [
                    Order::STATUS_COMPLETED,
                    Order::STATUS_CANCELLED_REQUEST,
                    Order::STATUS_CANCELLED_ORDER,
                ],
            ])
            ->exists();

        if ($hasForbiddenOrders) {
            return ApiResponse::byResponseCode($apiCodes->HAS_ACTIVE_ORDER);
        }

        $user->is_deleted = 1;
        PushService::dropTokens();
        if ($user->save(false)) {
            return ApiResponse::byResponseCode($apiCodes->SUCCESS);
        }

        return ApiResponse::byResponseCode($apiCodes->INTERNAL_ERROR);
    }
}
