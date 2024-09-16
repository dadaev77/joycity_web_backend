<?php

namespace app\controllers\api\v1\buyer;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\BuyerController;
use app\models\Order;
use app\models\OrderDistribution;
use app\models\User;
use app\services\order\OrderDistributionService;
use app\services\order\OrderStatusService;
use app\services\output\OrderOutputService;
use Throwable;
use Yii;

class OrderController extends BuyerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['accept-order'] = ['put'];
        $behaviors['verbFilter']['actions']['decline-order'] = ['put'];
        $behaviors['verbFilter']['actions']['view'] = ['get'];
        $behaviors['verbFilter']['actions']['my'] = ['get'];
        $behaviors['verbFilter']['actions']['history'] = ['get'];
        $behaviors['verbFilter']['actions']['decline'] = ['put'];
        array_unshift($behaviors['access']['rules'], [
            'actions' => ['accept-order', 'decline-order', 'decline'],
            'allow' => false,
            'matchCallback' => fn() => User::getIdentity()->role === User::ROLE_BUYER_DEMO,
        ]);
        $behaviors['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->role === User::ROLE_BUYER_DEMO ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                false;
            Yii::$app->response->data = $response;
        };
        return $behaviors;
    }

    public function actionView(int $id)
    {
        $apiCodes = Order::apiCodes();
        $user = User::getIdentity();
        $order = Order::find()
            ->select(['id', 'buyer_id'])
            ->where(['id' => $id])
            ->one();

        if (!$order) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        if ($order->buyer_id !== $user->id) {
            return ApiResponse::code($apiCodes->NO_ACCESS);
        }

        return ApiResponse::info(OrderOutputService::getEntity($id));
    }

    public function actionMy(string $type = 'request')
    {
        $user = User::getIdentity();
        $order = Order::find()
            ->select('id')
            ->where(['buyer_id' => $user->id])
            ->orderBy(['id' => SORT_DESC]);

        if ($type === 'request') {
            $order->andWhere(['status' => Order::STATUS_GROUP_REQUEST_ACTIVE]);
        } else {
            $order->andWhere(['status' => Order::STATUS_GROUP_ORDER_ACTIVE]);
        }

        return ApiResponse::collection(
            OrderOutputService::getCollection($order->column()),
        );
    }

    public function actionHistory(string $type = 'request')
    {
        $user = User::getIdentity();
        $orderIds = Order::find()
            ->select(['id'])
            ->where(['buyer_id' => $user->id])
            ->orderBy(['id' => SORT_DESC]);

        if ($type === 'request') {
            $orderIds->andWhere([
                'status' => Order::STATUS_GROUP_REQUEST_CLOSED,
            ]);
        } else {
            $orderIds->andWhere(['status' => Order::STATUS_GROUP_ORDER_CLOSED]);
        }

        return ApiResponse::collection(
            OrderOutputService::getCollection($orderIds->column()),
        );
    }

    public function actionDecline(int $id)
    {
        $user = User::getIdentity();
        $apiCodes = Order::apiCodes();

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $order = Order::find()
                ->select(['id', 'buyer_id', 'status', 'product_id'])
                ->where([
                    'id' => $id,
                ])
                ->one();

            if (
                $order->buyer_id !== $user->id ||
                $order->status !== Order::STATUS_BUYER_ASSIGNED
            ) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $task = OrderDistribution::findOne(['order_id' => $id]);

            if (!$task) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            $status = OrderDistributionService::buyerDecline($task);

            if (!$status->success) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $status->reason,
                );
            }

            if (!$order->product_id) {
                $order->buyer_id = null;

                if (!$order->save(true, ['buyer_id'])) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $order->getFirstErrors(),
                    );
                }
                $orderStatus = OrderStatusService::created($order->id);
            } else {
                $orderDistribution = $order->orderDistribution;

                $orderDistribution->status = OrderDistribution::STATUS_CLOSED;

                if (!$orderDistribution->save()) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $orderDistribution->reason,
                    );
                }

                $orderStatus = OrderStatusService::cancelled($order->id);
            }

            if (!$orderStatus->success) {
                return ApiResponse::transactionCodeErrors(
                    $transaction,
                    $apiCodes->ERROR_SAVE,
                    $orderStatus->reason,
                );
            }

            $transaction?->commit();

            return ApiResponse::info(OrderOutputService::getEntity($id));
        } catch (Throwable $e) {
            $transaction?->rollBack();

            return ApiResponse::internalError($e);
        }
    }
}
