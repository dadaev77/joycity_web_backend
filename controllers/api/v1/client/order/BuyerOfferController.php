<?php

namespace app\controllers\api\v1\client\order;

use app\components\ApiResponse;
use app\controllers\api\v1\ClientController;
use app\models\BuyerOffer;
use app\models\User;
use app\services\order\OrderDistributionService;
use app\services\order\OrderStatusService;
use app\services\output\BuyerOfferOutputService;
use Throwable;
use Yii;

class BuyerOfferController extends ClientController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['accept'] = ['put'];
        $behaviors['verbFilter']['actions']['decline'] = ['put'];

        return $behaviors;
    }

    public function actionAccept(int $id)
    {
        $apiCodes = BuyerOffer::apiCodes();

        try {
            $user = User::getIdentity();
            $buyerOffer = BuyerOffer::findOne(['id' => $id]);

            if (!$buyerOffer) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $buyerOffer->status !== BuyerOffer::STATUS_WAITING ||
                $buyerOffer->order->created_by !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $transaction = Yii::$app->db->beginTransaction();
            $buyerOffer->status = BuyerOffer::STATUS_APPROVED;

            if (!$buyerOffer->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $buyerOffer->getFirstErrors(),
                );
            }

            $order = $buyerOffer->order;
            $order->price_inspection = $buyerOffer->price_inspection;
            $order->price_product = $buyerOffer->price_product;

            if (!$order->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $order->getFirstErrors(),
                );
            }

            $orderStatusChange = OrderStatusService::buyerOfferAccepted(
                $buyerOffer->order_id,
            );

            if (!$orderStatusChange->success) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $orderStatusChange->reason,
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                BuyerOfferOutputService::getEntity($buyerOffer->id),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    public function actionDecline(int $id)
    {
        $apiCodes = BuyerOffer::apiCodes();

        try {
            $user = User::getIdentity();
            $buyerOffer = BuyerOffer::findOne(['id' => $id]);

            if (!$buyerOffer) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $buyerOffer->status === BuyerOffer::STATUS_DECLINED ||
                $buyerOffer->order->created_by !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $transaction = Yii::$app->db->beginTransaction();
            $buyerOffer->status = BuyerOffer::STATUS_DECLINED;

            if (!$buyerOffer->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $buyerOffer->getFirstErrors(),
                );
            }

            $order = $buyerOffer->order;
            $order->buyer_id = null;
            $order->price_inspection = 0;
            $order->price_product = 0;

            if (!$order->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $order->getFirstErrors(),
                );
            }

            $orderStatusChange = OrderStatusService::created(
                $buyerOffer->order_id,
            );

            if (!$orderStatusChange->success) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $orderStatusChange->reason,
                );
            }

            $orderDistributionReload = OrderDistributionService::reloadDistributionTask(
                $order->id,
            );

            if (!$orderDistributionReload->success) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $orderDistributionReload->reason,
                );
            }

            $transaction?->commit();

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }
}
