<?php

namespace app\controllers\api\v1\fulfillment;

use app\components\ApiResponse;
use app\controllers\api\v1\FulfillmentController;
use app\models\Order;
use app\models\TypeDeliveryPoint;
use app\models\User;
use app\services\order\OrderStatusService;
use app\services\OrderTrackingConstructorService;
use app\services\output\OrderOutputService;
use Throwable;
use Yii;

class OrderController extends FulfillmentController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['my'] = ['get'];
        $behaviors['verbFilter']['actions']['history'] = ['get'];
        $behaviors['verbFilter']['actions']['arrived-to-fulfilment'] = ['put'];
        $behaviors['verbFilter']['actions']['view'] = ['get'];
        $behaviors['verbFilter']['actions']['ready-transferring-marketplace'] = ['put'];
        $behaviors['verbFilter']['actions']['finish-order'] = ['put'];

        return $behaviors;
    }

    public function actionView(int $id)
    {
        $apiCodes = Order::apiCodes();
        $user = User::getIdentity();
        $order = Order::find()
            ->select(['id', 'fulfillment_id'])
            ->where(['id' => $id])
            ->one();

        if (!$order) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        if ($order->fulfillment_id !== $user->id) {
            return ApiResponse::code($apiCodes->NO_ACCESS);
        }

        return ApiResponse::info(
            OrderOutputService::getEntity(
                $id,
                false, // Show deleted
                'small', // Size of output images
            ),
        );
    }

    public function actionMy()
    {
        $user = User::getIdentity();
        $orderIds = Order::find()
            ->select(['id'])
            ->where([
                'fulfillment_id' => $user->id,
                'status' => Order::STATUS_GROUP_ORDER_ACTIVE,
            ])
            ->orderBy(['id' => SORT_DESC]);

        return ApiResponse::collection(
            OrderOutputService::getCollection(
                $orderIds->column(),
                false, // Show deleted
                'small', // Size of output images
            ),
        );
    }

    public function actionArrivedToFulfilment(int $id)
    {
        try {
            $apiCodes = Order::apiCodes();
            $user = User::getIdentity();
            $order = Order::findOne(['id' => $id]);

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $order->status !== Order::STATUS_TRANSFERRING_TO_FULFILLMENT ||
                $order->fulfillment_id !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $orderStatusChange = OrderStatusService::arrivedToFulfilment(
                $order->id,
            );

            if (!$orderStatusChange->success) {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $orderStatusChange->reason,
                );
            }

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }

    public function actionReadyTransferringMarketplace(int $id)
    {
        try {
            $apiCodes = Order::apiCodes();
            $user = User::getIdentity();
            $order = Order::findOne(['id' => $id]);
            $transaction = Yii::$app->db->beginTransaction();

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $order->status !==
                Order::STATUS_FULFILLMENT_PACKAGE_LABELING_COMPLETE ||
                $order->fulfillment_id !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $orderStatusChange = OrderStatusService::readyTransferringToMarketplace(
                $order->id,
            );

            if (!$orderStatusChange->success) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $orderStatusChange->reason,
                );
            }

            $orderTracking = OrderTrackingConstructorService::redyTransferringToMarketplace(
                $order->id,
            );

            if (!$orderTracking->success) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $orderTracking->reason,
                );
            }

            $transaction?->commit();

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return ApiResponse::internalError($e);
        }
    }

    public function actionHistory()
    {
        $user = User::getIdentity();
        $orderIds = Order::find()
            ->select(['id', 'status'])
            ->where([
                'fulfillment_id' => $user->id,
                'status' => Order::STATUS_GROUP_ALL_CLOSED,
            ])
            ->orderBy(['id' => SORT_DESC]);

        return ApiResponse::collection(
            OrderOutputService::getCollection(
                $orderIds->column(),
                false, // Show deleted
                'small', // Size of output images
            ),
        );
    }

    public function actionFinishOrder(int $id)
    {
        $apiCodes = Order::apiCodes();

        try {
            $user = User::getIdentity();
            $order = Order::findOne(['id' => $id]);

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                $order->fulfillment_id !== $user->id ||
                $order->type_delivery_point_id !==
                TypeDeliveryPoint::TYPE_FULFILLMENT ||
                $order->status !== Order::STATUS_FULLY_PAID
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $transaction = Yii::$app->db->beginTransaction();
            $orderStatusChange = OrderStatusService::completed($id);

            if (!$orderStatusChange->success) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $orderStatusChange->reason,
                );
            }

            $orderTracking = OrderTrackingConstructorService::itemArrived($id);

            if (!$orderTracking->success) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->NOT_VALID,
                    $orderTracking->reason,
                );
            }

            $transaction?->commit();

            return ApiResponse::info(
                OrderOutputService::getEntity(
                    $order->id,
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
