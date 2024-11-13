<?php

namespace app\controllers\api\v1\manager\order;

use app\components\ApiResponse;
use app\controllers\api\v1\ManagerController;
use app\models\FulfillmentOffer;
use app\models\Order;
use app\models\User;
use app\services\order\OrderStatusService;
use app\services\OrderTrackingConstructorService;
use app\services\output\FulfillmentOfferOutputService;
use Throwable;
use Yii;

class FulfillmentOfferController extends ManagerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['paid'] = ['put'];
        return $behaviors;
    }

    /**
     * @OA\Put(
     *     path="/api/v1/manager/order/fulfillment-offer/paid/{id}",
     *     summary="Отметить предложение выполнения как оплаченное",
     *     tags={"FulfillmentOffer"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID предложения выполнения",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Предложение выполнения успешно отмечено как оплаченное"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Предложение выполнения или заказ не найден"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Нет доступа к предложению выполнения"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации параметров"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера"
     *     )
     * )
     */
    public function actionPaid(int $id)
    {
        $apiCodes = Order::apiCodes();

        try {
            $request = Yii::$app->request;
            $user = User::getIdentity();

            $fulfillmentOffer = FulfillmentOffer::findOne(['id' => $id]);

            if (!$fulfillmentOffer) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            $order = $fulfillmentOffer->order;

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $order->status !==
                Order::STATUS_FULLY_DELIVERED_TO_MARKETPLACE ||
                $order->manager_id !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            if (
                $fulfillmentOffer->status !== FulfillmentOffer::STATUS_ACCEPTED
            ) {
                return ApiResponse::codeErrors($apiCodes->BAD_REQUEST, [
                    'status' => 'Order has no approved buyerOffer',
                ]);
            }

            $transaction = Yii::$app->db->beginTransaction();

            $order->price_fulfilment = $fulfillmentOffer->overall_price;

            if (!$order->save()) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->NOT_VALID,
                    $order->getFirstErrors(),
                );
            }

            $orderStatusChange = OrderStatusService::fullyPaid($order->id);

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
                    $apiCodes->NOT_VALID,
                    $orderTracking->reason,
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                FulfillmentOfferOutputService::getEntity($id),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }
}
