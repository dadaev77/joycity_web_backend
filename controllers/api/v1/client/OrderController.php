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
use app\services\CronService;
use app\services\notification\NotificationConstructor;
use app\services\order\OrderDistributionService;
use app\services\order\OrderStatusService;
use app\services\output\OrderOutputService;
use app\services\price\OrderPriceService;
use app\services\RateService;
use app\services\SaveModelService;
use app\services\TypeDeliveryService;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\web\UploadedFile;

use app\controllers\CronController;
use app\models\OrderDistribution;
use app\services\modificators\price\OrderPrice;
use app\services\TranslationService;
use app\services\chats\ChatService;

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
        $repeatOrderId = $request->post('repeat_order_id');
        $repeatImagesToKeep = $request->post('repeat_images_to_keep');
        $fulfillmentId = $request->post('fulfillment_id');
        $expected_price_per_item = $request->post('expected_price_per_item') ?? 0;
        $transaction = null;
        $typeDeliveryPointId = $request->post('type_delivery_point_id');
        (bool) $withProduct = false;
        $currency = $user->settings->currency;

        try {
            $randomManager = User::find()
                ->select(['id'])
                ->where(['role' => User::ROLE_MANAGER])
                ->orderBy('RAND()')
                ->one();

            $order = new Order();
            $order->created_by = $user->id;
            $order->created_at = date('Y-m-d H:i:s');
            $order->status = Order::STATUS_CREATED;
            $order->manager_id = $randomManager->id;
            $order->currency = $currency;


            $order->type_delivery_point_id = $typeDeliveryPointId;
            $order->expected_price_per_item = $expected_price_per_item;

            if ((int) $typeDeliveryPointId === TypeDeliveryPoint::TYPE_FULFILLMENT) {
                $fulfillmentUser = User::find()
                    ->where([
                        'id' => $fulfillmentId,
                        'role' => User::ROLE_FULFILLMENT,
                    ])
                    ->one();
                if ($fulfillmentUser) {
                    $order->fulfillment_id = $fulfillmentId;
                } else {
                    return ApiResponse::code($apiCodes->NOT_FOUND);
                }
            }

            $availableTypeIdsDeliveries = TypeDeliveryService::getTypeDeliveryIdsBySubcategory($request->post('subcategory_id'));

            if (
                !in_array(
                    (int) $request->post('type_delivery_id'),
                    $availableTypeIdsDeliveries,
                    true,
                )
            ) {
                return ApiResponse::code($apiCodes->BAD_REQUEST, [
                    'type_delivery_id' =>
                    'Type delivery is not available for this subcategory',
                ]);
            }

            $transaction = Yii::$app->db->beginTransaction();


            try {
                $translation = TranslationService::translateProductAttributes(
                    $request->post()['product_name'],
                    $request->post()['product_description'],
                );
                $translations = $translation->result;
                foreach ($translations as $key => $value) {
                    $order->{'product_name_' . $key} = $value['name'];
                    $order->{'product_description_' . $key} = $value['description'];
                }
            } catch (Throwable $e) {
                $translations = [
                    'ru' => [
                        'name' => $request->post()['product_name'],
                        'description' => $request->post()['product_description'],
                    ],
                    'en' => [
                        'name' => $request->post()['product_name'],
                        'description' => $request->post()['product_description'],
                    ],
                    'zh' => [
                        'name' => $request->post()['product_name'],
                        'description' => $request->post()['product_description'],
                    ],
                ];

                foreach ($translations as $key => $value) {
                    $order->{'product_name_' . $key} = $value['name'];
                    $order->{'product_description_' . $key} = $value['description'];
                }

                \Yii::$app->telegramLog->send('error', 'Ошибка при переводе названия и описания продукта: ' . $e->getMessage());
                return ApiResponse::byResponseCode(ResponseCodes::getStatic()->INTERNAL_ERROR, [
                    'error' => $e->getMessage(),
                    'text' => 'Error translating order name and description. Check translation service',
                ]);
            }

            

            $orderSave = SaveModelService::loadValidateAndSave(

                $order,
                [
                    'product_id',
                    'product_name',
                    'product_description',
                    'product_name_ru',
                    'product_description_ru',
                    'product_name_en',
                    'product_description_en',
                    'product_name_zh',
                    'product_description_zh',
                    'expected_quantity',
                    'expected_packaging_quantity',
                    'subcategory_id',
                    'type_packaging_id',
                    'type_delivery_id',
                    'type_delivery_point_id',
                    'delivery_point_address_id',
                    'is_need_deep_inspection',
                ],
                $transaction,
                true,
            );

            if (!$orderSave->success) {
                Yii::$app->telegramLog->send('error', 'Ошибка при создании заказа: ' . $orderSave->reason);
                return $orderSave->apiResponse;
            }
            
            ChatService::CreateGroupChat(
                'Order ' . $order->id,
                $user->id,
                $order->id,
                [
                    'deal_type' => 'order',
                    'participants' => [$user->id, $order->manager_id],
                    'group_name' => 'client_manager',
                ]
            );
            
            if ($order->product_id) {
                $withProduct = true;

                $buyerId = $order->product->buyer_id;
                $distributionStatus = OrderDistributionService::createDistributionTask($order->id, $buyerId);

                if (!$distributionStatus->success) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $distributionStatus->reason,
                    );
                }

                $buyerAcceptStatus = OrderDistributionService::buyerAccept(
                    $distributionStatus->result,
                    $buyerId,
                );

                if (!$buyerAcceptStatus->success) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $buyerAcceptStatus->reason,
                    );
                }

                $orderChangeStatus = OrderStatusService::buyerAssigned(
                    $order->id,
                );

                if (!$orderChangeStatus->success) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $orderChangeStatus->reason,
                    );
                }

                // create group chat for order with manager, buyer and client
                ChatService::createGroupChat(
                    'Order ' . $order->id,
                    $user->id,
                    $order->id,
                    [
                        'deal_type' => 'order',
                        'participants' => [$user->id, $order->manager_id, $buyerId],
                        'group_name' => 'client_buyer_manager',
                    ]
                );
            } else {
                $distributionStatus = OrderDistributionService::createDistributionTask($order->id);
                if (!$distributionStatus->success) {
                    $transaction?->rollBack();
                    return ApiResponse::codeErrors(
                        $apiCodes->ERROR_SAVE,
                        $distributionStatus->reason,
                    );
                }
            }

            $attachmentsToLink = [];

            if ($images) {
                $attachmentSaveResponse = AttachmentService::writeFilesCollection(
                    $images,
                );

                if (!$attachmentSaveResponse->success) {
                    $transaction?->rollBack();

                    return ApiResponse::byResponseCode(
                        $apiCodes->INTERNAL_ERROR,
                        ['errors' => ['images' => 'Failed to save images']],
                    );
                }

                $attachmentsToLink = array_merge(
                    $attachmentsToLink,
                    $attachmentSaveResponse->result,
                );
            }

            if ($repeatOrderId && $repeatImagesToKeep) {
                $repeatOrder = Order::findOne(['id' => $repeatOrderId]);
                if ($repeatOrder && $repeatOrder->created_by === $user->id) {
                    $attachmentsToKeep = Attachment::find()
                        ->joinWith([
                            'orderLinkAttachments' => fn($q) => $q->where([
                                'order_id' => $repeatOrder->id,
                            ]),
                        ])
                        ->where(['attachment.id' => $repeatImagesToKeep])
                        ->all();

                    $attachmentsToLink = array_merge(
                        $attachmentsToLink,
                        $attachmentsToKeep,
                    );
                }
            }

            if ($attachmentsToLink) {
                $order->linkAll('attachments', $attachmentsToLink);
            }

            NotificationConstructor::orderOrderCreated(
                $order->manager_id,
                $order->id,
            );

            $transaction?->commit();

            if ($orderSave->success) {
                if (!$withProduct) {
                    $distTaskID = OrderDistribution::find()->where(['order_id' => $order->id])->one();
                    if ($distTaskID) {
                        exec('curl -X GET "' . $_ENV['APP_URL'] . '/cron/create?taskID=' . $distTaskID->id . '"');
                    }
                }
                return ApiResponse::byResponseCode(null, [
                    'info' => OrderOutputService::getEntity($order->id),
                    'message' => 'Order created successfully',
                ]);
            } else {
                Yii::$app->telegramLog->send('error', 'Заказ не сохранен с ID ' . $order->id . '. Ошибка: ' . $orderSave->reason);
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $orderSave->reason
                );
            }
        } catch (Throwable $e) {
            $transaction?->rollBack();
            Yii::$app->telegramLog->send('error', 'Заказ не сохранен с ID ' . $order->id . '. Ошибка: ' . $e->getMessage());
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
            ->select(['id', 'created_by'])
            ->where(['id' => $id])
            ->one();

        if (!$order) {
            return ApiResponse::byResponseCode($apiCodes->NOT_FOUND);
        }

        if ($order->created_by !== $user->id) {
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
    public function actionMy(string $type = 'request')
    {
        $user = User::getIdentity();
        $orderIds = Order::find()
            ->select(['id'])
            ->where(['created_by' => $user->id])
            ->orderBy(['id' => SORT_DESC]);

        if ($type === 'request') {
            $orderIds->andWhere([
                'status' => Order::STATUS_GROUP_REQUEST_ACTIVE,
            ]);
        } else {
            $orderIds->andWhere(['status' => Order::STATUS_GROUP_ORDER_ACTIVE]);
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
