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
        $buyers = \app\models\User::find()
            ->select(['id', 'organization_name', 'phone_number', 'role'])
            ->where(['role' => \app\models\User::ROLE_BUYER])
            ->all();

        if (!$buyers) {
            return \app\components\ApiResponse::byResponseCode($this->apiCodes->NOT_FOUND);
        }

        return \app\components\ApiResponse::byResponseCode($this->apiCodes->SUCCESS, [
            'users' => $buyers,
        ]);
    }

    public function actionView($id)
    {
        $buyer = \app\models\User::find()
            ->select(['id', 'organization_name', 'phone_number', 'role'])
            ->where(['id' => $id, 'role' => \app\models\User::ROLE_BUYER])
            ->one();

        if (!$buyer) {
            return \app\components\ApiResponse::byResponseCode($this->apiCodes->NOT_FOUND);
        }

        return \app\components\ApiResponse::byResponseCode($this->apiCodes->SUCCESS, [
            'user' => $buyer,
        ]);
    }

    public function actionUpdateOrder($id)
    {
        $post = \Yii::$app->request->post();
        $buyerId = $post['buyer_id'];

        $order = \app\models\Order::findOne(['id' => $id]);
        if (!$order) {
            return \app\components\ApiResponse::byResponseCode($this->apiCodes->NOT_FOUND, ['message' => 'Order not found']);
        }

        $buyer = \app\models\User::findOne(['id' => $buyerId, 'role' => \app\models\User::ROLE_BUYER]);
        if (!$buyer) {
            return \app\components\ApiResponse::byResponseCode($this->apiCodes->NOT_FOUND, ['message' => 'Buyer not found']);
        }

        $order->buyer_id = $buyerId;
        if (!$order->save()) {
            return \app\components\ApiResponse::byResponseCode($this->apiCodes->BAD_REQUEST, [
                'errors' => $order->errors
            ]);
        }

        return \app\components\ApiResponse::byResponseCode($this->apiCodes->SUCCESS, [
            'order' => $order,
        ]);
    }
}
