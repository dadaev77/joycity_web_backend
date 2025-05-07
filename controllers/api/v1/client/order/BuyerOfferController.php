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

    /**
     * @OA\Put(
     *     path="/api/v1/client/order/buyer-offer/{id}/accept",
     *     summary="Accept a buyer offer",
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

            \Yii::$app->telegramLog->send('success', [
                'Предложение принято',
                'ID заказа: ' . $order->id,
                'ID покупателя: ' . $order->created_by,
            ], 'client');


            return ApiResponse::info(
                BuyerOfferOutputService::getEntity($buyerOffer->id),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();
            \Yii::$app->telegramLog->send('error', [
                'Клиент не может откликнуться на заявку байера',
                "Предложение ID: {$id}",
                $e->getMessage(),
            ], 'buyer');

            \Yii::$app->telegramLog->sendAlert('critical', [
                'Клиент не может откликнуться на заявку байера',
                "Предложение ID: {$id}",
                $e->getMessage(),
            ], 'critical');

            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/client/order/buyer-offer/{id}/decline",
     *     summary="Decline a buyer offer",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Offer declined successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Offer not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No access to decline the offer"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
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

            $transaction?->commit();

            \Yii::$app->telegramLog->send('success', [
                'Предложение отклонено',
                'ID предложения: ' . $buyerOffer->id,
                'ID заказа: ' . $order->id,
                'ID покупателя: ' . $order->created_by,
            ], 'client');

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            \Yii::$app->telegramLog->send('error', [
                'Клиент не может отклонить предложение байера',
                "Предложение ID: {$id}",
                $e->getMessage(),
            ], 'buyer');

            \Yii::$app->telegramLog->sendAlert('critical', [
                'Клиент не может отклонить предложение байера',
                "Предложение ID: {$id}",
                $e->getMessage(),
            ], 'critical');

            return ApiResponse::internalError($e);
        }
    }
}
