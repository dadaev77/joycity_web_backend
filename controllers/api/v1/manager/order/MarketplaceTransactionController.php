<?php

namespace app\controllers\api\v1\manager\order;

use app\components\ApiResponse;
use app\controllers\api\v1\ManagerController;
use app\models\FulfillmentMarketplaceTransaction;
use app\models\User;
use app\services\MarketplaceTransactionService;
use app\services\order\OrderStatusService;
use Throwable;
use Yii;

class MarketplaceTransactionController extends ManagerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['accept'] = ['put'];

        return $behaviors;
    }

    public function actionAccept($id)
    {
        try {
            $apiCodes = FulfillmentMarketplaceTransaction::apiCodes();
            $user = User::getIdentity();
            $marketplaceTransaction = FulfillmentMarketplaceTransaction::findOne(
                $id,
            );

            if (!$marketplaceTransaction) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            $order = $marketplaceTransaction->order;

            if (
                $order->manager_id !== $user->id ||
                $marketplaceTransaction->status ===
                    FulfillmentMarketplaceTransaction::STATUS_PAID
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $marketplaceTransactionInfo = MarketplaceTransactionService::getDeliveredCountInfo(
                $marketplaceTransaction->order_id,
            );

            $marketplaceTransaction->status =
                FulfillmentMarketplaceTransaction::STATUS_PAID;

            $transaction = Yii::$app->db->beginTransaction();

            if (!$marketplaceTransaction->save()) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $marketplaceTransaction->getFirstErrors(),
                );
            }

            if ($marketplaceTransactionInfo['full']) {
                $orderStatusChange = OrderStatusService::fullyPaid($order->id);

                if (!$orderStatusChange->success) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $orderStatusChange->reason,
                    );
                }
            }

            $transaction?->commit();

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }
}
