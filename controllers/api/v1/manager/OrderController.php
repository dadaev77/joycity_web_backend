<?php

namespace app\controllers\api\v1\manager;

use app\components\ApiResponse;
use app\controllers\api\v1\ManagerController;
use app\models\Order;
use app\models\TypeDeliveryPoint;
use app\models\User;
use app\services\order\OrderStatusService;
use app\services\OrderTrackingConstructorService;
use app\services\output\OrderOutputService;
use Throwable;
use Yii;
use app\services\UserActionLogService as LogService;

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
                $order->status !== Order::STATUS_TRANSFERRING_TO_WAREHOUSE ||
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
            return ApiResponse::internalError($e);
        }
    }

    public function actionFinishOrder()
    {
        $apiCodes = Order::apiCodes();

        try {
            $request = Yii::$app->request;
            $order_id = $request->post('order_id');
            $user = User::getIdentity();
            $order = Order::findOne(['id' => $order_id]);
            LogService::info('Initialize method finishOrder. Order id: ' . $order_id . ' Manager id: ' . $user->id . ' User email: ' . $user->email);

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }
            LogService::log('order found');
            if (
                $order->manager_id !== $user->id ||
                $order->type_delivery_point_id !==
                TypeDeliveryPoint::TYPE_WAREHOUSE ||
                $order->status !== Order::STATUS_ARRIVED_TO_WAREHOUSE
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }
            LogService::log('access allowed for user ' . $user->email);
            $transaction = Yii::$app->db->beginTransaction();
            LogService::log('transaction started');
            $orderStatusChange = OrderStatusService::completed($order->id);

            if (!$orderStatusChange->success) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $orderStatusChange->reason,
                );
            }
            LogService::log('order status changed to completed');
            $orderTracking = OrderTrackingConstructorService::itemArrived(
                $order_id,
            );
            LogService::log('order tracking constructor for order id: ' . $order_id);
            if (!$orderTracking->success) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->NOT_VALID,
                    $orderTracking->reason,
                );
            }
            LogService::log('Order tracking constructor for order id: ' . $order_id . ' by Manager id: ' . $user->id);
            $transaction?->commit();

            return ApiResponse::info(OrderOutputService::getEntity($order->id));
        } catch (Throwable $e) {
            LogService::danger('Error finish order for order id: ' . $order_id . ' by Manager id: ' . $user->id);
            isset($transaction) && $transaction->rollBack();
            return ApiResponse::internalError($e);
        }
    }

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
            OrderOutputService::getCollection($queryModel->column()),
        );
    }

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
            OrderOutputService::getEntity($order->id),
        );
    }
}
