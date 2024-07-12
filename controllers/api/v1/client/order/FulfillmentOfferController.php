<?php

namespace app\controllers\api\v1\client\order;

use app\components\ApiResponse;
use app\controllers\api\v1\ClientController;
use app\models\FulfillmentOffer;
use app\models\OrderRate;
use app\models\Rate;
use app\models\User;
use app\services\output\FulfillmentOfferOutputService;
use Throwable;
use Yii;

class FulfillmentOfferController extends ClientController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['accept'] = ['put'];
        return $behaviors;
    }

    public function actionAccept(int $id)
    {
        $apiCodes = FulfillmentOffer::apiCodes();

        try {
            $user = User::getIdentity();
            $fulfillmentOffer = FulfillmentOffer::findOne(['id' => $id]);

            if (!$fulfillmentOffer) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $fulfillmentOffer->status !==
                    FulfillmentOffer::STATUS_CREATED ||
                $fulfillmentOffer->order->created_by !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $transaction = Yii::$app->db->beginTransaction();
            $fulfillmentOffer->status = FulfillmentOffer::STATUS_ACCEPTED;

            if (!$fulfillmentOffer->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $fulfillmentOffer->getFirstErrors(),
                );
            }

            $order = $fulfillmentOffer->order;

            $order->price_fulfilment = $fulfillmentOffer->overall_price;

            if (!$order->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $order->getFirstErrors(),
                );
            }

            $rate = Rate::find()
                ->orderBy(['id' => SORT_DESC])
                ->one();

            if (!$rate) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->BAD_REQUEST,
                    ['rate' => 'Rate not found'],
                );
            }

            $orderRate = new OrderRate();
            $orderRate->order_id = $order->id;
            $orderRate->RUB = $rate->RUB;
            $orderRate->CNY = $rate->CNY;
            $orderRate->USD = $rate->USD;
            $orderRate->type = OrderRate::TYPE_FULFILLMENT_PAYMENT;

            if (!$orderRate->save()) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->NOT_VALID,
                    $orderRate->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                FulfillmentOfferOutputService::getEntity($fulfillmentOffer->id),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }
}
