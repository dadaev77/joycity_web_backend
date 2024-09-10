<?php

namespace app\controllers\api\v1\manager;

use app\components\ApiResponse;
use app\controllers\api\v1\ManagerController;
use app\models\Chat;
use app\models\User;
use app\services\output\ChatOutputService;
use Throwable;
use Yii;
use app\models\Order;
use app\services\UserActionLogService as LogService;

class ChatController extends ManagerController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['index'] = ['get'];

        return $behaviors;
    }

    public function actionIndex()
    {
        // define vars
        $user = User::getIdentity();
        LogService::log('manager: ' . $user->name . '. ChatController::actionIndex');
        $request = Yii::$app->request;
        $type = $request->get('group', '');
        LogService::log('type: ' . $type);
        $isArchive = (int) $request->get('is_archive', 0);
        try {
            $query = Chat::find()
                ->select(['chat.id'])
                ->joinWith([
                    'chatUsers' => fn($q) => $q
                        ->select(['id', 'user_id', 'chat_id'])
                    // ->where([
                    //     'user_id' => $user->id,
                    // ]),
                ])
                ->where(['group' => $type])
                ->andWhere(['is_archive' => boolval($isArchive)]);

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

        $orders = Order::find()
            ->where(['like', 'id', $query])
            ->andWhere(['manager_id' => $user->id])
            ->all();

        if (!$orders) return ApiResponse::code($apiCodes->NOT_FOUND);


        foreach ($orders as $order) {

            $chats = Chat::find()
                ->where(['order_id' => $order->id])
                ->andWhere(['is_archive' => 0])
                ->all();

            if (!$chats) return ApiResponse::code($apiCodes->NOT_FOUND);

            $outChats = [];
            // Filter out chats with group 'manager_buyer' or 'manager_fulfillment'
            foreach ($chats as $chat) {
                if (!in_array($chat->group, ['manager_buyer', 'manager_fulfillment'])) {
                    $outChats[] = $chat->id;
                }
            }

            $result[] = [
                'order_id' => $order->id,
                'buyer_id' => $order->buyer_id,
                'manager_id' => $order->manager_id,
                'fulfillment_id' => $order->fulfillment_id ? $order->fulfillment_id : 'не назначен',
                'chats' => ChatOutputService::getCollection($outChats),
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
            ->where(['order_id' => $orderId])
            ->andWhere(['is_archive' => 0])
            ->all();

        $outChats = [];
        // Filter out chats with group 'manager_buyer' or 'manager_fulfillment'
        foreach ($chats as $chat) {
            if (!in_array($chat->group, [
                // 'client_manager',
                'manager_buyer',
                'manager_fulfilment',
            ])) {
                $outChats[] = $chat->id;
            }
        }

        if (empty($outChats)) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        return ApiResponse::collection(ChatOutputService::getCollection($outChats));
    }
}
