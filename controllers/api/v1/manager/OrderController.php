<?php

namespace app\controllers\api\v1\manager;

use app\controllers\api\v1\ManagerController;
use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\models\Order;
use app\models\TypeDeliveryPoint;
use app\models\User;
use app\services\order\OrderStatusService;
use app\services\OrderTrackingConstructorService;
use app\services\output\OrderOutputService;
use app\services\PushService;
use Throwable;
use Yii;

class OrderController extends ManagerController
{

    public $apiCodes;

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
        $behaviors['verbFilter']['actions']['finish-order'] = ['post'];
        $behaviors['verbFilter']['actions']['arrived-to-warehouse'] = ['put'];
        $behaviors['verbFilter']['actions']['update-order'] = ['put'];
        $behaviors['verbFilter']['actions']['update'] = ['put'];

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

            \Yii::$app->telegramLog->send('success', [
                'Заказ прибыл на склад',
                'ID заказа: ' . $order->id,
                'ID менеджера: ' . $user->id,
            ], 'manager');

            return ApiResponse::code($apiCodes->SUCCESS);
        } catch (Throwable $e) {
            \Yii::$app->telegramLog->send('error', [
                'Ошибка при отметке заказа как прибывшего на склад',
                'Текст ошибки: ' . $e->getMessage(),
                'Трассировка: ' . $e->getTraceAsString(),
            ], 'manager');
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

            \Yii::$app->telegramLog->send('success', [
                'Заказ завершен',
                'ID заказа: ' . $order->id,
                'ID менеджера: ' . $user->id,
            ], 'manager');

            return ApiResponse::info(
                OrderOutputService::getEntity(
                    $order->id,
                    false, // Show deleted
                    'small', // Size of output images
                ),
            );
        } catch (Throwable $e) {
            \Yii::$app->telegramLog->send('error', [
                'Ошибка при завершении заказа',
                'Текст ошибки: ' . $e->getMessage(),
                'Трассировка: ' . $e->getTraceAsString(),
            ], 'manager');

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


    public function actionUpdateOrder()
    {
        $id = \Yii::$app->request->post('id');
        $buyerId = \Yii::$app->request->post('buyer_id');

        if (!$id || !$buyerId) {
            return \app\components\ApiResponse::byResponseCode($this->apiCodes->NOT_FOUND, ['message' => 'Order or buyer not found']);
        }

        $order = \app\models\Order::findOne(['id' => $id]);
        if (!$order) {
            return \app\components\ApiResponse::byResponseCode($this->apiCodes->NOT_FOUND, ['message' => 'Order not found']);
        }

        // Проверка статуса заказа
        if ($order->status === Order::STATUS_BUYER_OFFER_CREATED) {
            return \app\components\ApiResponse::byResponseCode($this->apiCodes->NO_ACCESS, ['message' => 'Невозможно изменить байера, когда заказ в статусе "Сделано предложение"']);
        }

        // Проверяем, что заказ находится в статусе ожидания предложения
        if (!in_array($order->status, [Order::STATUS_WAITING_FOR_BUYER_OFFER, Order::STATUS_BUYER_ASSIGNED])) {
            return \app\components\ApiResponse::byResponseCode($this->apiCodes->NO_ACCESS, ['message' => 'Изменить байера можно только в статусе "Ожидание предложения"']);
        }

        $buyer = \app\models\User::findOne(['id' => $buyerId, 'role' => \app\models\User::ROLE_BUYER]);

        if (!$buyer) {
            return \app\components\ApiResponse::byResponseCode($this->apiCodes->NOT_FOUND, ['message' => 'Buyer not found']);
        }

        $order->buyer_id = $buyerId;
        $save = $order->save();
        if (!$save) return ApiResponse::codeErrors($this->apiCodes->ERROR_SAVE, $order->errors);

        // Отправляем уведомление новому байеру
        $language = $buyer->getSettings()->application_language;
        PushService::sendPushNotification($buyerId, [
            'title' => Yii::t('order', 'new_order_for_buyer', [], $language),
            'body' => Yii::t('order', 'new_order_for_buyer_text', ['order_id' => $order->id], $language),
        ], true);

        \Yii::$app->telegramLog->send('success', [
            'Заказ обновлен менеджером',
            'ID заказа: ' . $order->id,
            'ID продавца: ' . $buyerId,
            'ID менеджера: ' . \Yii::$app->user->id,
        ], 'manager');

        return \app\components\ApiResponse::byResponseCode($this->apiCodes->SUCCESS, [
            'order' => $order,
        ]);
    }

    /**
     * @OA\PUT(
     *     path="/api/v1/manager/order/{id}",
     *     security={{"Bearer": {}}},
     *     summary="Изменить данные заказа клиента",
     *     tags={"Order"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID заказа",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="buyer_id", type="integer", example=12)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Заказ успешно обновлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=200),
     *             @OA\Property(property="response", type="object",
     *                 @OA\Property(property="id", type="integer", example=3),
     *                 @OA\Property(property="created_at", type="string", example="15/01/25"),
     *                 @OA\Property(property="status", type="string", example="status"),
     *                 @OA\Property(property="buyer_id", type="integer", example=12),
     *                 @OA\Property(property="manager_id", type="integer", example=2)
     *             ),
     *             @OA\Property(property="code", type="integer", example=1000),
     *             @OA\Property(property="codeKey", type="string", example="SUCCESS"),
     *             @OA\Property(property="message", type="string", example=""),
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден",
     *         @OA\JsonContent(
     *             @OA\Property(property="statusCode", type="integer", example=404),
     *             @OA\Property(property="response", type="null"),
     *             @OA\Property(property="code", type="integer", example=2004),
     *             @OA\Property(property="codeKey", type="string", example="NOT_FOUND"),
     *             @OA\Property(property="message", type="string", example="NOT_FOUND"),
     *             @OA\Property(property="success", type="boolean", example=false)
     *         )
     *     )
     * )
     */
    public function actionUpdate()
    {
        $id = Yii::$app->request->post('id');
        $buyerId = Yii::$app->request->post('buyer_id');

        $apiCodes = Order::apiCodes();
        $user = Yii::$app->user->getIdentity();
        $order = Order::findOne(['id' => $id]);
        $buyer = User::findOne(['id' => $buyerId, 'role' => User::ROLE_BUYER]);

        if (!$id || !$buyerId) return ApiResponse::code($this->apiCodes->NOT_FOUND, ['message' => 'Order or buyer not provided']);
        if (!$order || !$buyer) return ApiResponse::code($this->apiCodes->NOT_FOUND, ['message' => 'Order or buyer not exists']);
        if ($order->manager_id !== $user->id) {
            return ApiResponse::code($apiCodes->NO_ACCESS);
        }

        // Проверка статуса заказа
        if ($order->status === Order::STATUS_BUYER_OFFER_CREATED) {
            return ApiResponse::code($apiCodes->NO_ACCESS, ['message' => 'Невозможно изменить байера, когда заказ в статусе "Сделано предложение"']);
        }

        // Проверяем, что заказ находится в статусе ожидания предложения
        if (!in_array($order->status, [Order::STATUS_WAITING_FOR_BUYER_OFFER, Order::STATUS_BUYER_ASSIGNED])) {
            return ApiResponse::code($apiCodes->NO_ACCESS, ['message' => 'Изменить байера можно только в статусе "Ожидание предложения"']);
        }

        try {
            $order->buyer_id = $buyerId;
            $save = $order->save();
            if (!$save) return ApiResponse::codeErrors($apiCodes->ERROR_SAVE, $order->errors);


            $language = $buyer->getSettings()->application_language;
            PushService::sendPushNotification($buyerId, [
                'title' => Yii::t('order', 'Новый заказ', [], $language),
                'body' => Yii::t('order', 'Новый заказ для покупателя', ['order_id' => $order->id], $language),
            ], true);

            \Yii::$app->telegramLog->send('success', [
                'Заказ обновлен менеджером',
                'ID заказа: ' . $order->id,
                'ID продавца: ' . $buyerId,
                'ID менеджера: ' . \Yii::$app->user->id,
            ], 'manager');

            return ApiResponse::info(OrderOutputService::getEntity($order->id));
        } catch (Throwable $e) {
            \Yii::$app->telegramLog->send('error', [
                'Ошибка при обновлении заказа менеджером',
                'Текст ошибки: ' . $e->getMessage(),
                'Трассировка: ' . $e->getTraceAsString(),
            ], 'manager');
            return ApiResponse::internalError($e->getMessage());
        }
    }
}
