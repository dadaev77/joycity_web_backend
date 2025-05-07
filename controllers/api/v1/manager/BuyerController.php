<?php

namespace app\controllers\api\v1\manager;

use app\controllers\api\v1\ManagerController;
use app\components\response\ResponseCodes;
use app\services\chats\ChatService;
use app\models\BuyerOffer;


class BuyerController extends ManagerController
{
    protected $apiCodes;
    public function init()
    {
        parent::init();
        $this->apiCodes = ResponseCodes::getStatic();
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
        $buyerId = \Yii::$app->request->post('buyer_id');
        $order = \app\models\Order::findOne(['id' => $id]);
        $buyer = \app\models\User::findOne(['id' => $buyerId, 'role' => \app\models\User::ROLE_BUYER]);

        if (!$order || !$buyer) return \app\components\ApiResponse::byResponseCode($this->apiCodes->NOT_FOUND);
        $order->buyer_id = $buyerId;

        $chats = \app\models\Chat::find()->where(['order_id' => $order->id])->all();

        $chat_with_buyer = null;
        foreach ($chats as $chat) {
            if ($chat->metadata['group_name'] == 'client_buyer_manager') {
                $chat_with_buyer = $chat;
            }
        }

        if (!$chat_with_buyer) return \app\components\ApiResponse::byResponseCode($this->apiCodes->NOT_FOUND);

        $chat_with_buyer->status = 'archived';
        $chat_with_buyer->save();

        $oldBuyerOffers = BuyerOffer::find()->where(['order_id' => $order->id])->all();
        foreach ($oldBuyerOffers as $oldBuyerOffer) {
            $oldBuyerOffer->delete();
        }

        $orderStatus = \app\services\order\OrderStatusService::waitingForBuyerOffer($order->id);
        if (!$orderStatus->success) {
            \Yii::$app->telegramLog->send('error', 'Ошибка при установке статуса заказа на ожидание предложения от байера: ' . json_encode($orderStatus->reason));
            return \app\components\ApiResponse::byResponseCode($this->apiCodes->BAD_REQUEST, [
                'errors' => $orderStatus->reason,
            ]);
        }

        ChatService::CreateGroupChat(
            'Order ' . $order->id,
            \Yii::$app->user->id,
            $order->id,
            [
                'deal_type' => 'order',
                'participants' => [$order->created_by, $order->manager_id, $buyerId],
                'group_name' => 'client_buyer_manager',
            ]
        );

        if (!$order->save()) {
            \Yii::$app->telegramLog->send('error', [
                'Ошибка при обновлении заказа менеджером',
                'Текст ошибки: ' . json_encode($order->errors),
                'ID заказа: ' . $order->id,
                'ID продавца: ' . $buyerId,
                'ID менеджера: ' . \Yii::$app->user->id,
            ], 'manager');

            \Yii::$app->telegramLog->sendAlert('critical', [
                'Ошибка при обновлении заказа менеджером',
                'Текст ошибки: ' . json_encode($order->errors),
                'ID заказа: ' . $order->id,
                'ID продавца: ' . $buyerId,
                'ID менеджера: ' . \Yii::$app->user->id,
            ], 'critical');

            return \app\components\ApiResponse::byResponseCode($this->apiCodes->BAD_REQUEST, [
                'errors' => $order->errors
            ]);
        }

        \Yii::$app->telegramLog->send('success', [
            'Заказ обновлен менеджером',
            'ID менеджера: ' . \Yii::$app->user->id,
            'ID заказа: ' . $order->id,
            'ID покупателя: ' . $buyerId,
        ], 'manager');

        return \app\components\ApiResponse::byResponseCode($this->apiCodes->SUCCESS, [
            'order' => $order,
        ]);
    }
}
