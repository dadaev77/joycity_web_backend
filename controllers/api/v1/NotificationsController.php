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
