<?php

namespace app\controllers\api\v1\internal;

use app\components\ApiResponse;
use app\controllers\api\v1\InternalController;
use app\models\Order;
use app\services\output\OrderOutputService;
use Yii;

class OrderController extends InternalController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['view'] = ['get'];

        return $behaviors;
    }

    public function actionIndex()
    {
        $request = Yii::$app->request;
        $queryModel = Order::find()->select(['order.id']);

        if ($type = $request->get('type', 'order')) {
            $queryModel->andWhere([
                'IN',
                'order.status',
                $type === 'request'
                    ? Order::STATUS_GROUP_REQUEST
                    : Order::STATUS_GROUP_ORDER,
            ]);
        }

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

        return ApiResponse::collection(
            OrderOutputService::getCollection($queryModel->column()),
        );
    }

    public function actionView(int $id)
    {
        $apiCodes = Order::apiCodes();
        $order = Order::findOne(['id' => $id]);

        if (!$order) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        return ApiResponse::info(OrderOutputService::getEntity($id));
    }
}
