<?php

namespace app\controllers\api\v1\manager\order;

use app\components\ApiResponse;
use app\controllers\api\v1\ManagerController;
use app\helpers\POSTHelper;
use app\models\BuyerOffer;
use app\models\Order;
use app\models\OrderRate;
use app\models\Rate;
use app\models\TypeDeliveryPoint;
use app\models\User;
use app\services\chats\ChatService;
use app\services\notification\NotificationConstructor;
use app\services\order\OrderStatusService;
use app\services\OrderTrackingConstructorService;
use app\services\output\BuyerOfferOutputService;
use app\services\output\OrderOutputService;
use Throwable;
use Yii;

class BuyerOfferController extends ManagerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['update'] = ['put'];
        $behaviors['verbFilter']['actions']['paid'] = ['put'];

        return $behaviors;
    }

    /**
     * @OA\Put(
     *     path="/api/v1/manager/order/buyer-offer/paid/{id}",
     *     summary="Отметить предложение покупателя как оплаченное",
     *     tags={"BuyerOffer"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID предложения покупателя",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Предложение покупателя успешно отмечено как оплаченное"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Предложение покупателя не найдено"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Нет доступа к предложению"
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

            $buyerOffer = BuyerOffer::findOne(['id' => $id]);

            if (!$buyerOffer) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            $order = $buyerOffer->order;

            if (
                $order->status !== Order::STATUS_BUYER_OFFER_ACCEPTED ||
                $order->manager_id !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $buyerOffer = BuyerOffer::findOne([
                'id' => $id,
                'status' => BuyerOffer::STATUS_APPROVED,
            ]);

            if (!$buyerOffer) {
                return ApiResponse::codeErrors($apiCodes->BAD_REQUEST, [
                    'buyer_offer' => 'Order has no approved buyerOffer',
                ]);
            }

            $transaction = Yii::$app->db->beginTransaction();

            $order->price_product = $buyerOffer->price_product;
            $order->price_inspection = $buyerOffer->price_inspection;
            $order->amount_of_space = null;

            if (!$order->save()) {
                \Yii::$app->telegramLog->send('error', 'Не удалось подтвердить оплату предложения продавца: ' . json_encode($order->getFirstErrors()));
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->NOT_VALID,
                    $order->getFirstErrors(),
                );
            }

            $orderStatusChange = OrderStatusService::transferringToBuyer(
                $order->id,
            );

            if (
                $order->type_delivery_point_id ===
                TypeDeliveryPoint::TYPE_FULFILLMENT
            ) {
                NotificationConstructor::orderOrderCreated(
                    $order->fulfillment_id,
                    $order->id,
                );


                ChatService::CreateGroupChat(
                    'Order ' . $order->id,
                    $order->manager_id,
                    $order->id,
                    [
                        'deal_type' => 'order',
                        'participants' => [$order->fulfillment_id, $order->manager_id, $order->created_by],
                        'group_name' => 'client_fulfillment_manager',
                    ]
                );
            }

            if (!$orderStatusChange->success) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $orderStatusChange->reason,
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
            $orderRate->type = OrderRate::TYPE_PRODUCT_PAYMENT;

            if (!$orderRate->save()) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->NOT_VALID,
                    $orderRate->getFirstErrors(),
                );
            }

            $orderTracking = OrderTrackingConstructorService::buyerAwaiting(
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

            return ApiResponse::info(BuyerOfferOutputService::getEntity($id));
        } catch (Throwable $e) {
            Yii::$app->telegramLog->send('error', 'Ошибка при оплате предложения продавца: ' . $e->getMessage());
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/manager/order/buyer-offer/update/{id}",
     *     summary="Обновить предложение покупателя",
     *     tags={"BuyerOffer"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID предложения покупателя",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="price_product", type="number", example=100.0),
     *             @OA\Property(property="total_quantity", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Предложение покупателя успешно обновлено"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Предложение покупателя не найдено"
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
    public function actionUpdate(int $id)
    {
        $apiCodes = Order::apiCodes();

        try {
            $user = User::getIdentity();
            $postPayload = POSTHelper::getPostWithKeys([
                'price_product',
                'total_quantity',
            ]);

            $buyerOffer = BuyerOffer::findOne(['id' => $id]);

            if (!$buyerOffer) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $buyerOffer->order->status !==
                Order::STATUS_BUYER_INSPECTION_COMPLETE ||
                $buyerOffer->order->manager_id !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $transaction = Yii::$app->db->beginTransaction();
            $buyerOffer->load($postPayload, '');

            if (!$buyerOffer->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $buyerOffer->getFirstErrors(),
                );
            }

            $buyerOffer->order->load($postPayload, '');

            if (!$buyerOffer->order->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $buyerOffer->order->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                OrderOutputService::getEntity(
                    $buyerOffer->order_id,
                    false, // Show deleted
                    'small', // Size of output images
                ),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }
}
