<?php

namespace app\controllers\api\v1\buyer\order;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\BuyerController;
use app\models\Chat;
use app\models\OrderDistribution;
use app\models\User;
use app\services\chat\ChatConstructorService;
use app\services\order\OrderDistributionService;
use app\services\order\OrderStatusService;
use app\services\output\OrderDistributionOutputService;
use Throwable;
use Yii;

class DistributionController extends BuyerController
{
    private const DISTRIBUTION_TASK_CACHE_KEY = 'distribution_task_<USER_ID>';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['status'] = ['get'];
        $behaviors['verbFilter']['actions']['accept'] = ['put'];
        $behaviors['verbFilter']['actions']['decline'] = ['put'];
        array_unshift($behaviors['access']['rules'], [
            'actions' => ['accept', 'decline'],
            'allow' => false,
            'matchCallback' => fn() => User::getIdentity()->role === User::ROLE_BUYER_DEMO,
        ]);
        $behaviors['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->role === User::ROLE_BUYER_DEMO ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                false;
            Yii::$app->response->data = $response;
        };
        return $behaviors;
    }

    public function actionStatus()
    {
        $user = User::getIdentity();
        $activeTasks = Yii::$app->cache->getOrSet(
            self::getCacheKey($user->id),
            function () use ($user) {
                $query = OrderDistribution::find()
                    ->select(['id'])
                    ->where([
                        'current_buyer_id' => $user->id,
                        'status' => OrderDistribution::STATUS_IN_WORK,
                    ]);

                return OrderDistributionOutputService::getCollection(
                    $query->column(),
                );
            },
            5,
        );

        return ApiResponse::collection($activeTasks);
    }

    public function actionAccept(int $id)
    {
        $apiCodes = OrderDistribution::apiCodes();

        try {
            $user = User::getIdentity();
            $task = OrderDistribution::findOne(['id' => $id]);

            if (!$task) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if ($task->current_buyer_id !== $user->id) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $transaction = Yii::$app->db->beginTransaction();
            $status = OrderDistributionService::buyerAccept($task, $user->id);
            $order = $task->order;

            if (!$status->success) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $status->reason,
                );
            }

            $orderStatusChange = OrderStatusService::buyerAssigned(
                $task->order_id,
            );

            if (!$orderStatusChange->success) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $orderStatusChange->reason,
                );
            }

            $conversationClient = ChatConstructorService::createChatOrder(
                Chat::GROUP_CLIENT_BUYER_MANAGER,
                [$user->id, $order->created_by, $order->manager_id],
                $order->id,
            );

            if (!$conversationClient->success) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $conversationClient->reason,
                );
            }

            // $conversationManager = ChatConstructorService::createChatOrder(
            //     Chat::GROUP_MANAGER_BUYER,
            //     [$user->id, $order->manager_id],
            //     $order->id,
            // );

            // if (!$conversationManager->success) {
            //     $transaction?->rollBack();

            //     return ApiResponse::codeErrors(
            //         $apiCodes->ERROR_SAVE,
            //         $conversationManager->reason,
            //     );
            // }

            $transaction?->commit();

            return ApiResponse::info(
                OrderDistributionOutputService::getEntity($task->id),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    public function actionDecline(int $id)
    {
        $apiCodes = OrderDistribution::apiCodes();

        try {
            $user = User::getIdentity();
            $task = OrderDistribution::findOne(['id' => $id]);

            if (!$task) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if ($task->current_buyer_id !== $user->id) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $status = OrderDistributionService::buyerDecline($task);

            if (!$status->success) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $status->reason,
                );
            }

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    private static function getCacheKey(int $userId): string
    {
        return str_replace(
            '<USER_ID>',
            $userId,
            self::DISTRIBUTION_TASK_CACHE_KEY,
        );
    }
}
