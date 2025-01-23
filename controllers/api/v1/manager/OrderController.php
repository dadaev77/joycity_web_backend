<?php

namespace app\controllers\api\v1\manager;

use app\controllers\api\v1\ManagerController;
use app\components\ApiResponse;
use app\models\Order;
use app\models\TypeDeliveryPoint;
use app\models\User;
use app\services\order\OrderStatusService;
use app\services\OrderTrackingConstructorService;
use app\services\output\OrderOutputService;
use Throwable;
use Yii;

class OrderController extends ManagerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['view'] = ['get'];
        $behaviors['verbFilter']['actions']['finish-order'] = ['post'];
        $behaviors['verbFilter']['actions']['arrived-to-warehouse'] = ['put'];

        return $behaviors;
    }

    /**
     * @OA\Put(
     *     path="/api/v1/manager/order/arrived-to-warehouse/{id}",
     *     security={{"Bearer": {}}},
     *     summary="Отметить заказ как прибывший на склад",
     *     tags={"Order"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID заказа",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Заказ успешно отмечен как прибывший на склад"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Нет доступа к заказу"
     *     )
     * )
     */
    public function actionArrivedToWarehouse(int $id)
    {
        try {
            $apiCodes = Order::apiCodes();
            $user = User::getIdentity();
            $order = Order::findOne(['id' => $id]);

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if (
                // $order->status !== Order::STATUS_TRANSFERRING_TO_WAREHOUSE ||
                $order->manager_id !== $user->id
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $orderStatusChange = OrderStatusService::arrivedToWarehouse(
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
            return ApiResponse::internalError($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/manager/order/finish-order",
     *     security={{"Bearer": {}}},
     *     summary="Завершить заказ",
     *     tags={"Order"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="order_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Заказ успешно завершен"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Нет доступа к заказу"
     *     )
     * )
     */
    public function actionFinishOrder()
    {
        $apiCodes = Order::apiCodes();

        try {
            $request = Yii::$app->request;
            $order_id = $request->post('order_id');
            $user = User::getIdentity();
            $order = Order::findOne(['id' => $order_id]);

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }
            if (
                $order->manager_id !== $user->id
                // $order->type_delivery_point_id !==
                // TypeDeliveryPoint::TYPE_WAREHOUSE ||
                // $order->status !== Order::STATUS_ARRIVED_TO_WAREHOUSE
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }
            $transaction = Yii::$app->db->beginTransaction();
            $orderStatusChange = OrderStatusService::completed($order->id);

            if (!$orderStatusChange->success) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $orderStatusChange->reason,
                );
            }
            $orderTracking = OrderTrackingConstructorService::itemArrived(
                $order_id,
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

    /**
     * @OA\Get(
     *     path="/api/v1/manager/order",
     *     security={{"Bearer": {}}},
     *     summary="Получить список заказов",
     *     tags={"Order"},
     *     @OA\Response(
     *         response=200,
     *         description="Успешно получен список заказов"
     *     )
     * )
     */
    public function actionIndex()
    {
        $apiCodes = Order::apiCodes();
        $user = User::getIdentity();
        $request = Yii::$app->request;
        $type = $request->get('type', 'order');
        $queryModel = Order::find()
            ->select(['order.id'])
            ->where(['order.manager_id' => $user->id])
            ->orderBy(['id' => SORT_DESC])
            ->andWhere([
                'IN',
                'order.status',
                $type === 'request'
                    ? Order::STATUS_GROUP_REQUEST_ACTIVE
                    : Order::STATUS_GROUP_ORDER_ACTIVE,
            ]);

        if ($query = $request->get('query')) {
            $queryModel->andWhere(['LIKE', 'order.id', "%$query%", false]);
        }

        if ($from = $request->get('from')) {
            $queryModel->andWhere(['>=', 'order.created_at', $from]);
        }

        if ($to = $request->get('to')) {
            $queryModel->andWhere([
                '<=',
                'order.created_at',
                date('Y-m-d', strtotime($to . ' +1 day')),
            ]);
        }

        if ($status = $request->get('status')) {
            $queryModel->andWhere(['order.status' => $status]);
        }

        if ($emailBuyer = $request->get('email_buyer')) {
            $queryModel
                ->joinWith(['buyer' => fn($q) => $q->select(['id', 'email'])])
                ->andWhere(['LIKE', 'user.email', $emailBuyer]);
        }

        if ($emailClient = $request->get('email_client')) {
            $queryModel
                ->joinWith([
                    'createdBy' => fn($q) => $q->select(['id', 'email']),
                ])
                ->andWhere(['LIKE', 'user.email', $emailClient]);
        }

        return ApiResponse::codeCollection(
            $apiCodes->SUCCESS,
            OrderOutputService::getCollection(
                $queryModel->column(),
                false, // Show deleted
                'small', // Size of output images
            ),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/manager/order/{id}",
     *     security={{"Bearer": {}}},
     *     summary="Получить информацию о заказе",
     *     tags={"Order"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID заказа",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешно получена информация о заказе"
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
            ->select(['id', 'manager_id'])
            ->where(['id' => $id])
            ->one();

        if (!$order) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        if ($order->manager_id !== $user->id) {
            return ApiResponse::code($apiCodes->NO_ACCESS);
        }

        return ApiResponse::codeInfo(
            $apiCodes->SUCCESS,
            OrderOutputService::getEntity(
                $order->id,
                false, // Show deleted
                'small', // Size of output images
            ),
        );
    }
}
