<?php

namespace app\controllers\api\v1\buyer;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
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
        $behaviors['verbFilter']['actions']['search'] = ['get'];
        $behaviors['verbFilter']['actions']['get-chat'] = ['get'];
        array_unshift($behaviors['access']['rules'], [
            'actions' => ['get-chat'],
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

    /**
     * @OA\Get(
     *     path="/api/v1/buyer/chat",
     *     summary="Получить список чатов",
     *     @OA\Response(
     *         response=200,
     *         description="Список чатов успешно получен."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный запрос."
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/buyer/chat/search",
     *     summary="Поиск заказов и чатов",
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Поисковый запрос.",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список заказов и связанных с ними чатов успешно получен."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный запрос."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказы не найдены."
     *     )
     * )
     */
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
                ->andWhere(['like', 'group', Chat::GROUP_CLIENT_BUYER])
                ->andWhere(['is_archive' => 0])
                ->column();

            if (!$chats) return ApiResponse::code($apiCodes->NOT_FOUND);

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

    /**
     * @OA\Get(
     *     path="/api/v1/buyer/chat/get-chat",
     *     summary="Получить чаты для заказа",
     *     @OA\Parameter(
     *         name="order_id",
     *         in="query",
     *         required=true,
     *         description="ID заказа.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список чатов для указанного заказа успешно получен."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный запрос."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Чаты не найдены."
     *     )
     * )
     */
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
            ->andWhere(['like', 'group', 'client_buyer'])
            ->andWhere(['is_archive' => 0])
            ->column();

        // check if chats are found
        if (!$chats) return ApiResponse::code($apiCodes->NOT_FOUND);

        return ApiResponse::collection(ChatOutputService::getCollection($chats));
    }
}
