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

    /**
     * @OA\Put(
     *     path="/api/v1/client/order/fulfillment-offer/{id}/accept",
     *     summary="Accept a fulfillment offer",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Offer accepted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Offer not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No access to accept the offer"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
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
                Yii::$app->telegramLog->send('error', 'FulfillmentOfferController. fulfillment offer not saved with id ' . $fulfillmentOffer->id . '. Flow is incorrect. Error: ' . $fulfillmentOffer->getFirstErrors());
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $fulfillmentOffer->getFirstErrors(),
                );
            }

            $order = $fulfillmentOffer->order;

            $order->price_fulfilment = $fulfillmentOffer->overall_price;

            if (!$order->save()) {
                $transaction?->rollBack();
                Yii::$app->telegramLog->send('error', 'FulfillmentOfferController. order not saved with id ' . $order->id . '. Flow is incorrect. Error: ' . $order->getFirstErrors());
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $order->getFirstErrors(),
                );
            }

            $rate = Rate::find()
                ->orderBy(['id' => SORT_DESC])
                ->one();

            if (!$rate) {
                Yii::$app->telegramLog->send('error', 'FulfillmentOfferController. rate not found with id ' . $rate->id . '. Flow is incorrect. Error: ' . $rate->getFirstErrors());
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
                Yii::$app->telegramLog->send('error', 'FulfillmentOfferController. order rate not saved with id ' . $orderRate->id . '. Flow is incorrect. Error: ' . $orderRate->getFirstErrors());
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
            Yii::$app->telegramLog->send('error', 'FulfillmentOfferController. error: ' . $e->getMessage());
            isset($transaction) && $transaction->rollBack();
            return ApiResponse::internalError($e);
        }
    }
}
