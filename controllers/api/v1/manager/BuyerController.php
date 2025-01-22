<?php

namespace app\controllers\api\v1\manager;

use app\controllers\api\v1\ManagerController;



class BuyerController extends ManagerController
{
    protected $apiCodes;
    public function init()
    {
        parent::init();
        $this->apiCodes = \app\models\Order::apiCodes();
    }
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];
        $behaviors['verbFilter']['actions']['view'] = ['get'];
        $behaviors['verbFilter']['actions']['update-order'] = ['post'];
        return $behaviors;
    }

    public function actionIndex()
    {
        $buyers = \app\models\User::find()->where(['role' => \app\models\User::ROLE_BUYER])->all();
        if (!$buyers) return \app\components\ApiResponse::byResponseCode($this->apiCodes->NOT_FOUND);
        return \app\components\ApiResponse::byResponseCode($this->apiCodes->SUCCESS, [
            'users' => $buyers,
        ]);
    }

    public function actionView($id)
    {
        $buyer = \app\models\User::findOne(['id' => $id]);
        if (!$buyer) return \app\components\ApiResponse::byResponseCode($this->apiCodes->NOT_FOUND);
        return \app\components\ApiResponse::byResponseCode($this->apiCodes->SUCCESS, [
            'user' => $buyer,
        ]);
    }

    public function actionUpdateOrder()
    {
        $post = \Yii::$app->request->post();
        $buyerId = $post['buyer_id'];
        $orderId = $post['order_id'];
        $buyer = \app\models\User::findOne(['id' => $buyerId, 'role' => \app\models\User::ROLE_BUYER]);
        if (!$buyer) return \app\components\ApiResponse::byResponseCode($this->apiCodes->NOT_FOUND, ['message' => 'Buyer not found']);
        $order = \app\models\Order::findOne(['id' => $orderId]);
        if (!$order) return \app\components\ApiResponse::byResponseCode($this->apiCodes->NOT_FOUND, ['message' => 'Order not found']);
        // Update order buyer_id
        $order->buyer_id = $buyerId;
        // Save order
        if (!$order->save()) {
            return \app\components\ApiResponse::byResponseCode($this->apiCodes->BAD_REQUEST, [
                'errors' => $order->errors
            ]);
        }
        return \app\components\ApiResponse::byResponseCode($this->apiCodes->SUCCESS, ['order' => $order]);
    }
}
