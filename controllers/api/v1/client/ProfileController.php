<?php

namespace app\controllers\api\v1\client;

use app\components\ApiResponse;
use app\controllers\api\v1\ClientController;
use app\helpers\POSTHelper;
use app\models\Order;
use app\models\User;
use app\services\AttachmentService;
use app\services\output\ProfileOutputService;
use Throwable;
use Yii;
use yii\db\Exception;
use yii\web\UploadedFile;
use app\components\response\ResponseCodes;


class ProfileController extends ClientController
{
    public function behaviors()
    {
        $behaviours = parent::behaviors();
        $behaviours['verbFilter']['actions']['update'] = ['put'];
        $behaviours['verbFilter']['actions']['upload-avatar'] = ['post'];
        $behaviours['verbFilter']['actions']['self'] = ['get'];
        $behaviours['verbFilter']['actions']['delete'] = ['delete'];
        array_unshift($behaviours['access']['rules'], [
            'actions' => ['update', 'delete'],
            'allow' => false,
            'matchCallback' => fn() => User::getIdentity()->role === User::ROLE_CLIENT_DEMO,
        ]);
        $behaviours['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->role === User::ROLE_CLIENT_DEMO ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                false;
            Yii::$app->response->data = $response;
        };

        return $behaviours;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/client/profile/upload-avatar",
     *     summary="Загрузить аватар пользователя",
     *     description="Загружает аватар для текущего пользователя.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"images"},
     *             @OA\Property(property="images", type="array", @OA\Items(type="string", format="binary")),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Аватар успешно загружен"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Некорректные данные"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера"
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
     * @OA\Get(
     *     path="/api/v1/client/profile/self",
     *     summary="Получить информацию о текущем пользователе",
     *     description="Возвращает информацию о текущем пользователе.",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ с информацией о пользователе"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Пользователь не найден"
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
     * @OA\Put(
     *     path="/api/v1/client/profile/update",
     *     summary="Обновить информацию о пользователе",
     *     description="Обновляет информацию о пользователе на основе переданных данных.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "surname"},
     *             @OA\Property(property="name", type="string", example="Иван"),
     *             @OA\Property(property="surname", type="string", example="Иванов"),
     *             @OA\Property(property="phone_number", type="string", example="+123456789"),
     *             @OA\Property(property="organization_name", type="string", example="Организация"),
     *             @OA\Property(property="phone_country_code", type="string", example="RU"),
     *             @OA\Property(property="telegram", type="string", example="@ivan"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешное обновление информации о пользователе"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Некорректные данные"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Пользователь не найден"
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
        ]);

        $user->load($postParams, '');

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
     *     path="/api/v1/client/profile/delete",
     *     summary="Удалить профиль пользователя",
     *     description="Удаляет профиль текущего пользователя, если у него нет активных заказов.",
     *     @OA\Response(
     *         response=200,
     *         description="Профиль успешно удален"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="У пользователя есть активные заказы"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера"
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

        if ($user->save(false)) {
            return ApiResponse::byResponseCode($apiCodes->SUCCESS);
        }

        return ApiResponse::byResponseCode($apiCodes->INTERNAL_ERROR);
    }
}
