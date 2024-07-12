<?php

namespace app\controllers\api\v1\fulfillment\order;

use app\components\ApiResponse;
use app\controllers\api\v1\FulfillmentController;
use app\helpers\POSTHelper;
use app\models\FulfillmentMarketplaceTransaction;
use app\models\Order;
use app\models\User;
use app\services\MarketplaceTransactionService;
use app\services\notification\NotificationConstructor;
use app\services\order\OrderStatusService;
use app\services\OrderTrackingConstructorService;
use app\services\output\FulfillmentMarketplaceTransactionOutputService;
use Throwable;
use Yii;

class MarketplaceTransactionController extends FulfillmentController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['create'] = ['post'];

        return $behaviors;
    }

    public function actionCreate()
    {
        try {
            $apiCodes = FulfillmentMarketplaceTransaction::apiCodes();
            $user = User::getIdentity();
            $postParams = POSTHelper::getPostWithKeys([
                'order_id',
                'product_count',
            ]);

            $marketplaceTransaction = new FulfillmentMarketplaceTransaction();
            $marketplaceTransaction->load($postParams, '');
            $marketplaceTransaction->created_at = date('Y-m-d H:i:s');
            $marketplaceTransaction->fulfillment_id = $user->id;
            $marketplaceTransaction->status =
                FulfillmentMarketplaceTransaction::STATUS_CREATED;

            $order = Order::find()
                ->andWhere([
                    'id' => $marketplaceTransaction->order_id,
                    'fulfillment_id' => $user->id,
                ])
                ->one();

            if (
                !$order ||
                !in_array(
                    $order->status,
                    [
                        Order::STATUS_READY_TRANSFERRING_TO_MARKETPLACE,
                        Order::STATUS_PARTIALLY_DELIVERED_TO_MARKETPLACE,
                    ],
                    true,
                )
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $marketplaceTransactionInfo = MarketplaceTransactionService::getDeliveredCountInfo(
                $marketplaceTransaction->order_id,
                $marketplaceTransaction->product_count,
            );

            if (!$marketplaceTransactionInfo) {
                return ApiResponse::code($apiCodes->INTERNAL_ERROR);
            }

            if (!$marketplaceTransactionInfo['allowed']) {
                return ApiResponse::code(
                    $apiCodes->MARKETPLACE_DELIVERED_COUNT_MORE_THAN_ALLOWED,
                );
            }

            $marketplaceTransactionAmount = (static function () use (
                $marketplaceTransactionInfo,
                $marketplaceTransaction,
                $order,
            ) {
                $overallCount = $marketplaceTransactionInfo['all'];
                $currentCount = $marketplaceTransaction->product_count;
                $overallPrice = $order->fulfillmentOffer->overall_price;

                return round(
                    ($currentCount / $overallCount) * $overallPrice,
                    4,
                );
            })();
            $marketplaceTransaction->amount = $marketplaceTransactionAmount;

            $transaction = Yii::$app->db->beginTransaction();

            if (!$marketplaceTransaction->save()) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $marketplaceTransaction->getFirstErrors(),
                );
            }

            if (
                $order->status ===
                Order::STATUS_READY_TRANSFERRING_TO_MARKETPLACE
            ) {
                $orderStatusChange = OrderStatusService::partiallyDeliveredToMarketplace(
                    $order->id,
                );

                if (!$orderStatusChange->success) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $orderStatusChange->reason,
                    );
                }

                $orderTracking = OrderTrackingConstructorService::partiallyDeliveredToMarketplace(
                    $order->id,
                );

                if (!$orderTracking->success) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $orderTracking->reason,
                    );
                }
            }

            if ($marketplaceTransactionInfo['full']) {
                $orderStatusChange = OrderStatusService::fullyDeliveredToMarketplace(
                    $order->id,
                );

                if (!$orderStatusChange->success) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $orderStatusChange->reason,
                    );
                }

                $orderTracking = OrderTrackingConstructorService::fullyDeliveredToMarketplace(
                    $order->id,
                );

                if (!$orderTracking->success) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $orderTracking->reason,
                    );
                }
            }

            NotificationConstructor::orderOrderMarketplaceTransaction(
                $order->created_by,
                $order->id,
            );
            NotificationConstructor::orderOrderMarketplaceTransaction(
                $order->manager_id,
                $order->id,
            );

            $transaction?->commit();

            return ApiResponse::info(
                FulfillmentMarketplaceTransactionOutputService::getEntity(
                    $marketplaceTransaction->id,
                ),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }
}
