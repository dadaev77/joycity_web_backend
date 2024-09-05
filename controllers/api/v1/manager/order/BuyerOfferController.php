<?php

namespace app\controllers\api\v1\manager\order;

use app\components\ApiResponse;
use app\controllers\api\v1\ManagerController;
use app\helpers\POSTHelper;
use app\models\BuyerOffer;
use app\models\Chat;
use app\models\Order;
use app\models\OrderRate;
use app\models\Rate;
use app\models\TypeDeliveryPoint;
use app\models\User;
use app\services\chat\ChatConstructorService;
use app\services\notification\NotificationConstructor;
use app\services\order\OrderStatusService;
use app\services\OrderTrackingConstructorService;
use app\services\output\BuyerOfferOutputService;
use app\services\output\OrderOutputService;
use app\services\twilio\TwilioService;
use app\services\UserActionLogService as LogService;
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

            if (!$order->save()) {
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

                $conversationFulfilment = ChatConstructorService::createChatOrder(
                    Chat::GROUP_CLIENT_FULFILMENT,
                    [$order->created_by, $order->fulfillment_id],
                    $order->id,
                );

                if (!$conversationFulfilment->success) {
                    $transaction?->rollBack();

                    return ApiResponse::codeErrors(
                        $apiCodes->ERROR_SAVE,
                        $conversationFulfilment->reason,
                    );
                }

                // add manager of order to conversation with fulfillment
                LogService::log('start adding manager to conversation client and fulfillment');
                $personalId = User::find()->where(['id' => $order->manager_id])->one()->personal_id;
                LogService::log('Manager personalId: ' . $personalId);
                $addManagerToConversation = TwilioService::addUserToConversation($personalId, $conversationFulfilment->result->twilio_id);
                if (!$addManagerToConversation->success) {
                    LogService::danger('error adding manager to conversation' . json_encode($addManagerToConversation->reason));
                }
                LogService::success('manager added to conversation client and fulfillment');
                // end add manager to conversation

                $conversationManagerFulfilment = ChatConstructorService::createChatOrder(
                    Chat::GROUP_MANAGER_FULFILMENT,
                    [$order->fulfillment_id, $order->manager_id],
                    $order->id,
                );

                if (!$conversationManagerFulfilment->success) {
                    $transaction?->rollBack();

                    return ApiResponse::codeErrors(
                        $apiCodes->ERROR_SAVE,
                        $conversationManagerFulfilment->reason,
                    );
                }
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
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

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
                OrderOutputService::getEntity($buyerOffer->order_id),
            );
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }
}
