<?php

namespace app\controllers\api\v1\client;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\ClientController;
use app\helpers\POSTHelper;
use app\models\Attachment;
use app\models\Order;
use app\models\TypeDeliveryPoint;
use app\models\User;
use app\services\AttachmentService;
use app\services\notification\NotificationConstructor;
use app\services\order\OrderDistributionService;
use app\services\order\OrderStatusService;
use app\services\output\OrderOutputService;
use app\services\TypeDeliveryService;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\web\UploadedFile;
use app\models\OrderDistribution;
use app\services\modificators\price\OrderPrice;
use app\services\TranslationService;
use app\services\chats\ChatService;
use app\services\push\PushService;

class OrderController extends ClientController
{
    public function init()
    {
        parent::init();
        Yii::beginProfile('OrderOutput');
    }
    public function behaviors()
    {
        $behaviours = parent::behaviors();
        $behaviours['verbFilter']['actions']['create'] = ['post'];
        $behaviours['verbFilter']['actions']['update'] = ['put'];
        $behaviours['verbFilter']['actions']['set-link-tz'] = ['put'];
        $behaviours['verbFilter']['actions']['cancel'] = ['delete'];
        $behaviours['verbFilter']['actions']['view'] = ['get'];
        $behaviours['verbFilter']['actions']['my'] = ['get'];
        $behaviours['verbFilter']['actions']['history'] = ['get'];
        $behaviours['verbFilter']['actions']['fulfillment-list'] = ['get'];
        $behaviours['verbFilter']['actions']['calculate-price'] = ['post'];
        array_unshift($behaviours['access']['rules'], [
            'actions' => ['create', 'update', 'cancel'],
            'allow' => false,
            'matchCallback' => fn() => User::getIdentity()->role === User::ROLE_CLIENT_DEMO ? true : !User::getIdentity()->is_verified,
        ]);
        $behaviours['access']['denyCallback'] = static function () {
            $response =
                User::getIdentity()->role === User::ROLE_CLIENT_DEMO ?
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NOT_AUTHENTICATED) :
                ApiResponse::byResponseCode(ResponseCodes::getStatic()->NO_ACCESS_FOR_NOT_VERIFIED);

            Yii::$app->response->data = $response;
        };

        return $behaviours;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/client/order/create",
     *     security={{"Bearer":{}}},
     *     summary="Создать заказ",
     *     description="Создает новый заказ для текущего пользователя.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="product_id", type="integer", description="ID продукта"),
     *             @OA\Property(property="product_name", type="string", description="Название продукта"),
     *             @OA\Property(property="product_description", type="string", description="Описание продукта"),
     *             @OA\Property(property="expected_quantity", type="integer", description="Ожидаемое количество"),
     *             @OA\Property(property="type_delivery_id", type="integer", description="ID типа доставки"),
     *             @OA\Property(property="type_delivery_point_id", type="integer", description="ID точки доставки"),
     *             @OA\Property(property="delivery_point_address_id", type="integer", description="ID адреса доставки"),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string"), description="Изображения заказа")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Заказ успешно создан",
     *         @OA\JsonContent(
     *             @OA\Property(property="info", type="object", description="Информация о заказе"),
     *             @OA\Property(property="message", type="string", description="Сообщение об успешном создании")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный запрос"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Внутренняя ошибка сервера"
     *     )
     * )
     */
    public function actionCreate()
    {
        $user = User::getIdentity();
        $request = Yii::$app->request;
        $apiCodes = Order::apiCodes();
        $images = UploadedFile::getInstancesByName('images');
        $product_id = $request->post('product_id');
        $randomManager = User::find()
            ->select(['id'])
            ->where(['role' => User::ROLE_MANAGER])
            ->orderBy('RAND()')
            ->one();

        $order = new Order();
        $order->loadDefaultValues();
        $order->setAttributes([
            'created_by' => $user->id,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => Order::STATUS_CREATED,
            'currency' => $user->settings->currency,
            'type_delivery_point_id' => $request->post('type_delivery_point_id'),
            'expected_price_per_item' => $request->post('expected_price_per_item') ? (float)$request->post('expected_price_per_item') : null,
            'expected_quantity' => $request->post('expected_quantity') ?? 0,
            'expected_packaging_quantity' => $request->post('expected_packaging_quantity') ?? 0,
            'type_packaging_id' => $request->post('type_packaging_id') ?? null,
            'type_delivery_id' => $request->post('type_delivery_id') ?? null,
            'delivery_point_address_id' => $request->post('delivery_point_address_id') ?? null,
            'subcategory_id' => $request->post('subcategory_id') ?? null,
            'is_need_deep_inspection' => $request->post('is_need_deep_inspection') ?? 0,
            'repeat_order_id' => $request->post('repeat_order_id') ?? null,
            'repeat_images_to_keep' => $request->post('repeat_images_to_keep') ?? null,
            'manager_id' => $randomManager->id,
        ]);

        if ((int) $order->type_delivery_point_id === TypeDeliveryPoint::TYPE_FULFILLMENT) {
            $fulfillmentUser = User::find()
                ->where([
                    'id' => Yii::$app->request->post('fulfillment_id'),
                    'role' => User::ROLE_FULFILLMENT,
                ])->one();
            if ($fulfillmentUser) {
                $order->fulfillment_id = Yii::$app->request->post('fulfillment_id');
            } else {
                return ApiResponse::code($apiCodes->NOT_FOUND, [
                    'error' => 'Fulfillment user not found',
                    'fulfillment_id' => Yii::$app->request->post('fulfillment_id'),
                ]);
            }
        }

        if ($product_id) {
            $product = \app\models\Product::findOne($product_id);
            if (!$product) return ApiResponse::code($apiCodes->NOT_FOUND, ['error' => 'Product not found']);
            $order->buyer_id = $product->buyer_id;
            $order->product_id = $product_id;
        }

        $translations = [
            'ru' => ['name' => $request->post('product_name'), 'description' => $request->post('product_description')],
            'en' => ['name' => $request->post('product_name'), 'description' => $request->post('product_description')],
            'zh' => ['name' => $request->post('product_name'), 'description' => $request->post('product_description')],
        ];

        foreach ($translations as $lang => $values) {
            $order->{'product_name_' . $lang} = $values['name'];
            $order->{'product_description_' . $lang} = $values['description'];
        }
        Yii::$app->telegramLog->send('error', 'Некорректные данные для создания заказа');
        if (!$order->validate()) {
            \Yii::$app->telegramLog->send('error', 'Некорректные данные для создания заказа');
            return ApiResponse::codeErrors($apiCodes->NOT_VALID, $order->getErrors());
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$order->save()) {
                \Yii::$app->telegramLog->send('error', 'Не удалось создать заказ');
                throw new Exception('Order save error: ' . json_encode($order->getErrors()));
            }

            ChatService::CreateGroupChat('Order ' . $order->id, $user->id, $order->id, [
                'deal_type' => 'order',
                'participants' => [$user->id, $order->manager_id],
                'group_name' => 'client_manager',
            ], true);

            if ($product_id) {
                $buyer = User::findOne($product->buyer_id);
                $language = $buyer->getSettings()->application_language;

                $distributionStatus = OrderDistributionService::createDistributionTask($order->id, $product->buyer_id);
                if (!$distributionStatus->success) {
                    \Yii::$app->telegramLog->send('error', 'Не удалось создать задачу на распределение');
                    throw new Exception('Distribution error: ' . $distributionStatus->reason);
                }
                OrderDistributionService::buyerAccept($distributionStatus->result, $product->buyer_id);
                OrderStatusService::buyerAssigned($order->id);

                ChatService::CreateGroupChat('Order ' . $order->id, $user->id, $order->id, [
                    'deal_type' => 'order',
                    'participants' => [$user->id, $order->manager_id, $product->buyer_id],
                    'group_name' => 'client_buyer_manager',
                ]);

                PushService::sendPushNotification($product->buyer_id, [
                    'title' => Yii::t('order', 'new_order_for_buyer', [], $language),
                    'body' => Yii::t('order', 'new_order_for_buyer_text', ['order_id' => $order->id], $language),
                ], true);
            } else {
                $distribution = OrderDistributionService::createDistributionTask($order->id);
                if (!$distribution->success) {
                    \Yii::$app->telegramLog->send('error', 'Не удалось создать задачу на распределение');
                    throw new Exception('Distribution error: ' . $distribution->reason);
                }
                if (!\app\controllers\CronController::actionCreate($distribution->result->id)) {
                    \Yii::$app->telegramLog->send('error', 'Не удалось создать задачу cron для распределения заказа ' . $order->id);
                    throw new Exception('Cron task creation error: ' . $distribution->result->id);
                }
            }

            if ($images) {
                $attachmentResponse = AttachmentService::writeFilesCollection($images);
                if (!$attachmentResponse->success) {
                    \Yii::$app->telegramLog->send('error', 'Не удалось загрузить изображения');
                    throw new Exception('Image upload error: ' . json_encode($attachmentResponse->reason));
                }
                $order->linkAll('attachments', $attachmentResponse->result);
            }

            NotificationConstructor::orderOrderCreated($order->manager_id, $order->id);
            $transaction->commit();

            TranslationService::translateAttributes(
                $request->post('product_name'),
                $request->post('product_description'),
                'order',
                $order->id
            );


            return ApiResponse::byResponseCode(null, ['info' => OrderOutputService::getEntity($order->id)]);
        } catch (Throwable $e) {
            $transaction->rollBack();
            \Yii::$app->telegramLog->send('error', 'Не удалось создать заказ(общая ошибка): ' . $e->getMessage());
            return ApiResponse::internalError($e);
        }
    }


    /**
     * @OA\Put(
     *     path="/api/v1/client/order/update/{id}",
     *     security={{"Bearer":{}}},
     *     summary="Обновить заказ",
     *     description="Обновляет существующий заказ по ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="expected_quantity", type="integer", description="Ожидаемое количество"),
     *             @OA\Property(property="expected_price_per_item", type="number", format="float", description="Ожидаемая цена за единицу")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Заказ успешно обновлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="info", type="object", description="Информация о заказе"),
     *             @OA\Property(property="message", type="string", description="Сообщение об успешном обновлении")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный запрос"
     *     )
     * )
     */
    public function actionUpdate(int $id)
    {
        try {
            $request = Yii::$app->request;
            $user = User::getIdentity();
            $apiCodes = Order::apiCodes();
            $postParams = POSTHelper::getPostWithKeys([
                'expected_quantity',
                'expected_price_per_item',
                'expected_packaging_quantity',
                'subcategory_id',
                'type_packaging_id',
                'type_delivery_id',
                'type_delivery_point_id',
                'delivery_point_address_id',
                'is_need_deep_inspection',
                'fulfillment_id',
            ]);
            $order = Order::findOne(['id' => $id]);
            $transaction = null;

            if (!$order) {
                return ApiResponse::code($apiCodes->NOT_FOUND);
            }

            if ($order->created_by !== $user->id) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            if (!in_array($order->status, Order::STATUS_GROUP_ALLOWED, true)) {
                return ApiResponse::code($apiCodes->NO_ACCESS);
            }

            $availableTypeIdsDeliveries = TypeDeliveryService::getTypeDeliveryIdsBySubcategory(
                $postParams['subcategory_id'],
            );

            if (
                $postParams['type_delivery_id'] &&
                !in_array(
                    $request->post('type_delivery_id'),
                    $availableTypeIdsDeliveries,
                    true,
                )
            ) {
                return ApiResponse::code($apiCodes->BAD_REQUEST, [
                    'type_delivery_id' =>
                    'Type delivery is not available for this subcategory',
                ]);
            }

            $order->load($postParams, '');

            if (!$order->validate()) {
                return ApiResponse::codeErrors(
                    $apiCodes->NOT_VALID,
                    $order->getFirstErrors(),
                );
            }

            $transaction = Yii::$app->db->beginTransaction();

            if (!$order->save()) {
                $transaction?->rollBack();

                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $order->getFirstErrors(),
                );
            }

            $transaction?->commit();

            return ApiResponse::info(OrderOutputService::getEntity($order->id));
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();
            return ApiResponse::internalError($e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/client/order/cancel/{id}",
     *     security={{"Bearer":{}}},
     *     summary="Отменить заказ",
     *     description="Отменяет заказ по ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Заказ успешно отменен",
     *         @OA\JsonContent(
     *             @OA\Property(property="info", type="object", description="Информация о заказе"),
     *             @OA\Property(property="message", type="string", description="Сообщение об успешной отмене")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный запрос"
     *     )
     * )
     */
    public function actionCancel(int $id)
    {
        $apiCodes = Order::apiCodes();
        $user = User::getIdentity();
        $order = Order::findOne(['id' => $id]);

        if (!$order) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND);
        }
        if ($order->created_by !== $user->id) {
            return ApiResponse::byResponseCode($apiCodes->NO_ACCESS);
        }
        $orderChangeStatus = OrderStatusService::cancelled($order->id);
        if (!$orderChangeStatus->success) {
            return ApiResponse::byResponseCode(
                $apiCodes->ERROR_SAVE,
                $orderChangeStatus->reason,
            );
        }
        return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
            'info' => OrderOutputService::getEntity($order->id),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/client/order/{id}",
     *     security={{"Bearer":{}}},
     *     summary="Получить информацию о заказе",
     *     description="Возвращает информацию о заказе по ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="info", type="object", description="Информация о заказе")
     *         )
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
            ->select(['id', 'created_by', 'buyer_id'])
            ->where(['id' => $id])
            ->one();

        if (!$order) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND);
        }

        if ($order->created_by !== $user->id && $order->buyer_id !== $user->id) {
            return ApiResponse::code($apiCodes->NO_ACCESS);
        }

        return ApiResponse::byResponseCode($apiCodes->SUCCESS, [
            'info' => OrderOutputService::getEntity($id),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/client/order/my",
     *     security={{"Bearer": {}}},
     *     summary="Получить мои заказы",
     *     description="Возвращает заказы текущего пользователя.",
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", default="request")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="info", type="array", @OA\Items(type="object"), description="Список заказов")
     *         )
     *     )
     * )
     */
    public function actionMy(?string $type = null)
    {
        $user = User::getIdentity();
        $orderIds = Order::find()
            ->select(['id'])
            ->where([
                'OR',
                ['created_by' => $user->id],
                ['buyer_id' => $user->id]
            ])
            ->orderBy(['id' => SORT_DESC]);

        if ($type === 'request') {
            $orderIds->andWhere([
                'status' => Order::STATUS_GROUP_REQUEST_ACTIVE,
            ]);
        } elseif ($type === 'order') {
            // Для типа "order" показываем все активные заказы
            $orderIds->andWhere([
                'status' => Order::STATUS_GROUP_ORDER_ACTIVE,
            ]);
        }
        // Если type не указан, показываем все заказы

        return ApiResponse::collection(
            OrderOutputService::getCollection(
                $orderIds->column(),
                false,
                'small'
            ),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/client/order/history",
     *     security={{"Bearer": {}}},
     *     summary="Получить историю заказов",
     *     description="Возвращает историю заказов текущего пользователя.",
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", default="request")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="info", type="array", @OA\Items(type="object"), description="Список заказов")
     *         )
     *     )
     * )
     */
    public function actionHistory(string $type = 'request')
    {
        $user = User::getIdentity();
        $orderIds = Order::find()
            ->select(['id'])
            ->where(['created_by' => $user->id])
            ->orderBy(['id' => SORT_DESC]);

        if ($type === 'request') {
            $orderIds->andWhere([
                'status' => Order::STATUS_GROUP_REQUEST_CLOSED,
            ]);
        } else {
            $orderIds->andWhere(['status' => Order::STATUS_GROUP_ORDER_CLOSED]);
        }

        return ApiResponse::collection(
            OrderOutputService::getCollection(
                $orderIds->column(),
                false, // Show deleted
                'small', // Size of output images
            ),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/client/order/fulfillment-list",
     *     security={{"Bearer": {}}},
     *     summary="Получить список фулфилмента",
     *     description="Возвращает список пользователей с ролью фулфилмента.",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="info", type="array", @OA\Items(type="object"), description="Список фулфилмента")
     *         )
     *     )
     * )
     */
    public function actionFulfillmentList()
    {
        $users = User::find()
            ->select(['user.id'])
            ->joinWith(['userSettings', 'deliveryPointAddress'])
            ->where(['role' => User::ROLE_FULFILLMENT])
            ->asArray()
            ->all();

        $result = [];
        foreach ($users as $user) {
            if (
                $user['deliveryPointAddress'] !== null &&
                $user['userSettings'] !== null
            ) {
                $result[] = [
                    'fulfillment_id' => $user['id'],
                    'address' => $user['deliveryPointAddress']['address'],
                    'delivery_point_address_id' =>
                    $user['deliveryPointAddress']['id'],
                    'high_workload' =>
                    (bool) $user['userSettings']['high_workload'],
                ];
            }
        }

        return ApiResponse::collection($result);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/client/order/set-link-tz/{id}",
     *     security={{"Bearer": {}}},
     *     summary="Установить ссылку на TZ",
     *     description="Устанавливает ссылку на TZ для заказа по ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="link_tz", type="string", description="Ссылка на TZ")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ссылка на TZ успешно установлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="info", type="object", description="Информация о заказе")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заказ не найден"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Неверный запрос"
     *     )
     * )
     */
    public function actionSetLinkTz($id)
    {
        $request = Yii::$app->request;
        $linkTz = $request->post('link_tz');
        $apiCodes = Order::apiCodes();
        $user = User::getIdentity();
        $order = Order::findOne(['id' => $id, 'created_by' => $user->id]);

        if (!$order) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }
        if ($order->link_tz) {
            return ApiResponse::code($apiCodes->NO_ACCESS);
        }
        $order->link_tz = $linkTz;

        if (!$order->save()) {
            return ApiResponse::codeErrors(
                $apiCodes->ERROR_SAVE,
                $order->getFirstErrors(),
            );
        }
        return ApiResponse::info(OrderOutputService::getEntity($order->id));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/client/order/calculate-price",
     *     security={{"Bearer": {}}},
     *     summary="Рассчитать цену",
     *     description="Рассчитывает цену на основе переданных параметров.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="product_price", type="number", format="float", description="Цена продукта"),
     *             @OA\Property(property="product_quantity", type="integer", description="Количество продукта"),
     *             @OA\Property(property="product_width", type="number", format="float", description="Ширина продукта"),
     *             @OA\Property(property="product_height", type="number", format="float", description="Высота продукта"),
     *             @OA\Property(property="product_depth", type="number", format="float", description="Глубина продукта"),
     *             @OA\Property(property="product_weight", type="number", format="float", description="Вес продукта"),
     *             @OA\Property(property="packaging_quantity", type="integer", description="Количество упаковок"),
     *             @OA\Property(property="type_delivery_id", type="integer", description="ID типа доставки"),
     *             @OA\Property(property="type_packaging_id", type="integer", description="ID типа упаковки"),
     *             @OA\Property(property="calculation_type", type="string", description="Тип расчета")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ с рассчитанной ценой",
     *         @OA\JsonContent(
     *             @OA\Property(property="price", type="number", format="float", description="Рассчитанная цена")
     *         )
     *     )
     * )
     */
    public function actionCalculatePrice()
    {
        $request = Yii::$app->request;
        $product_price = $request->post('product_price');
        $product_quantity = $request->post('product_quantity');
        $product_width = $request->post('product_width');
        $product_height = $request->post('product_height');
        $product_depth = $request->post('product_depth');
        $product_weight = $request->post('product_weight');
        $packaging_quantity = $request->post('packaging_quantity');
        $type_delivery_id = $request->post('type_delivery_id');
        $type_packaging_id = $request->post('type_packaging_id');
        $calculation_type = $request->post('calculation_type');

        return ApiResponse::info([
            'price' => OrderPrice::calculatorFacade(
                $product_price,
                $product_quantity,
                $product_width,
                $product_height,
                $product_depth,
                $product_weight,
                $packaging_quantity,
                $type_delivery_id,
                $type_packaging_id,
                $calculation_type,
            ),
        ]);
    }
}
