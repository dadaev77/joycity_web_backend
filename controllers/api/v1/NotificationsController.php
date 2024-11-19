<?php

namespace app\controllers\api\v1;

use app\components\ApiResponse;
use app\controllers\api\V1Controller;
use app\models\Notification;
use app\models\search\User;
use app\services\output\NotificationOutputService;
use app\services\SaveModelService;
use Throwable;
use Yii;

class NotificationsController extends V1Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['unread'] = ['get'];
        $behaviors['verbFilter']['actions']['mark-as-read-entity'] = ['put'];
        $behaviors['verbFilter']['actions']['mark-as-read'] = ['put'];

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/notifications/index",
     *     summary="Получение уведомлений",
     *     description="Этот метод возвращает список уведомлений для текущего пользователя.",
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Parameter(
     *         name="entityId",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список уведомлений",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
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
    public function actionIndex(int $offset = 0, int $entityId = null)
    {
        try {
            $user = User::getIdentity();
            $query = Notification::find()
                ->select(['id'])
                ->limit(50)
                ->offset($offset)
                ->where(['user_id' => $user->id]);

            if ($entityId) {
                $query->andWhere(['entity_id' => $entityId]);
            }

            return ApiResponse::collection(
                NotificationOutputService::getCollection($query->column()),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/notifications/unread",
     *     summary="Получение непрочитанных уведомлений",
     *     description="Этот метод возвращает список непрочитанных уведомлений для текущего пользователя.",
     *     @OA\Response(
     *         response=200,
     *         description="Список непрочитанных уведомлений",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
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
    public function actionUnread()
    {
        try {
            $user = User::getIdentity();
            $query = Notification::find()
                ->select(['id'])
                ->where(['user_id' => $user->id])
                ->andWhere(['is_read' => 0])
                ->orderBy(['id' => SORT_DESC]);

            return ApiResponse::collection(
                NotificationOutputService::getCollection($query->column()),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/notifications/mark-as-read-entity",
     *     summary="Пометить уведомления как прочитанные по сущности",
     *     description="Этот метод помечает уведомления как прочитанные для указанной сущности.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"entity_id", "entity_type"},
     *             @OA\Property(property="entity_id", type="integer", example=1),
     *             @OA\Property(property="entity_type", type="string", example="order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Уведомления успешно помечены как прочитанные",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Уведомления не найдены",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Уведомления не найдены")
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
    public function actionMarkAsReadEntity()
    {
        try {
            $apiCodes = Notification::apiCodes();
            $request = Yii::$app->request;
            $entityId = $request->post('entity_id');
            $entityType = $request->post('entity_type');
            $user = User::getIdentity();
            $notificationCollection = Notification::findAll([
                'entity_id' => $entityId,
                'entity_type' => $entityType,
                'user_id' => $user->id,
            ]);

            if (!$notificationCollection) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            $transaction = Yii::$app->db->beginTransaction();

            foreach ($notificationCollection as $notification) {
                $notification->is_read = 1;
                $result = SaveModelService::validateAndSave(
                    $notification,
                    ['is_read'],
                    $transaction,
                );

                if (!$result->success) {
                    $transaction?->rollBack();

                    return $result->apiResponse;
                }
            }

            $transaction?->commit();

            return ApiResponse::collection(
                NotificationOutputService::getCollection(
                    array_column($notificationCollection, 'id'),
                ),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/notifications/mark-as-read/{id}",
     *     summary="Пометить уведомление как прочитанное",
     *     description="Этот метод помечает указанное уведомление как прочитанное.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Уведомление успешно помечено как прочитанное",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Уведомление не найдено",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Уведомление не найдено")
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
    public function actionMarkAsRead(int $id)
    {
        try {
            $apiCodes = Notification::apiCodes();
            $notification = Notification::findOne(['id' => $id]);
            $user = User::getIdentity();

            if (!$notification) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if ($notification->user_id !== $user->id) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $notification->is_read = 1;
            $result = SaveModelService::validateAndSave($notification, [
                'is_read',
            ]);

            if (!$result->success) {
                return $result->apiResponse;
            }

            return ApiResponse::info(NotificationOutputService::getEntity($id));
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }
}
