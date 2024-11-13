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

    /**
     * @OA\Get(
     *     path="/api/v1/fulfillment/order/view/{id}",
     *     summary="Просмотр заказа",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID заказа",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/fulfillment/order/my",
     *     summary="Получить мои заказы",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/v1/fulfillment/order/arrived-to-fulfilment/{id}",
     *     summary="Заказ прибыл на выполнение",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID заказа",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/v1/fulfillment/order/ready-transferring-marketplace/{id}",
     *     summary="Готов к передаче на рынок",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID заказа",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/fulfillment/order/history",
     *     summary="История заказов",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     )
     * )
     */
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
    /**
     * @OA\Put(
     *     path="/api/v1/fulfillment/order/finish-order/{id}",
     *     summary="Завершить заказ",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID заказа",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     )
     * )
     */
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
