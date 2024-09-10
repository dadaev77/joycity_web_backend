<?php

namespace app\controllers\api\v1\buyer;

use app\components\ApiResponse;
use app\controllers\api\v1\BuyerController;
use app\models\Chat;
use app\models\User;
use app\services\output\ChatOutputService;
use Throwable;
use Yii;
use app\models\Order;

class ChatController extends BuyerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];

        return $behaviors;
    }

    public function actionIndex()
    {
        try {
            $user = User::getIdentity();
            $request = Yii::$app->request;
            $type = $request->get('group', '');
            $isArchive = (int) $request->get('is_archive', 0);
            $query = Chat::find()
                ->select(['chat.id'])
                ->joinWith([
                    'chatUsers' => fn($q) => $q
                        ->select(['id', 'user_id', 'chat_id'])
                        ->where([
                            'user_id' => $user->id,
                        ]),
                ])
                ->where(['group' => $type]);

            if ($isArchive === 1) {
                $query->andWhere(['is_archive' => 1]);
            }

            if ($isArchive === 0) {
                $query->andWhere(['is_archive' => 0]);
            }

            return ApiResponse::collection(
                ChatOutputService::getCollection($query->column()),
            );
        } catch (Throwable $e) {
            return ApiResponse::internalError($e);
        }
    }
    public function actionSearch()
    {
        // define variables
        $result = [];
        $request = Yii::$app->request;
        $user = User::getIdentity();
        $apiCodes = Order::apiCodes();
        $query = $request->get('query');

        // check if user is authorized and query is not empty
        if (!$user) return ApiResponse::code($apiCodes->NOT_AUTHORIZED);
        if (!$query) return ApiResponse::code($apiCodes->BAD_REQUEST);

        // find orders by query and buyer_id to exlude orders that not belongs to user
        $orders = Order::find()
            ->where(['like', 'id', $query])
            ->andWhere(['buyer_id' => $user->id])
            ->all();

        // check if orders are found
        if (!$orders) return ApiResponse::code($apiCodes->NOT_FOUND);

        // get chats for each order
        foreach ($orders as $order) {
            $chats = Chat::find()
                ->select('id')
                ->where(['order_id' => $order->id])
                ->andWhere(['like', 'group', '_buyer'])
                ->andWhere(['is_archive' => 0])
                ->column();

            $result[] = [
                'order_id' => $order->id,
                'buyer_id' => $order->buyer_id,
                'manager_id' => $order->manager_id,
                'fulfillment_id' => $order->fulfillment_id ? $order->fulfillment_id : 'не назначен',
                'chats' => ChatOutputService::getCollection($chats),
            ];
        }
        return ApiResponse::collection($result);
    }
    public function actionGetChat()
    {
        // define variables
        $user = User::getIdentity();
        $request = Yii::$app->request;
        $orderId = $request->get('order_id');
        $apiCodes = Order::apiCodes();

        // check if user is authorized and order_id is not empty
        if (!$user) return ApiResponse::code($apiCodes->NOT_AUTHORIZED);
        if (!$orderId) return ApiResponse::code($apiCodes->BAD_REQUEST);

        // get chats for order
        $chats = Chat::find()
            ->select('id')
            ->where(['order_id' => $orderId])
            ->andWhere(['is_archive' => 0])
            ->all();

        $outChats = [];
        foreach ($chats as $chat) {
            if (!in_array($chat->group, ['manager_buyer'])) {
                $outChats[] = $chat->id;
            }
        }

        if (!$chats) return ApiResponse::code($apiCodes->NOT_FOUND);

        return ApiResponse::collection(ChatOutputService::getCollection($outChats));
    }
}
