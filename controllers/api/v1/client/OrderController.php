<?php

namespace app\controllers\api\v1\client;

use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\controllers\api\v1\ClientController;
use app\helpers\POSTHelper;
use app\models\Attachment;
use app\models\Chat;
use app\models\Order;
use app\models\TypeDeliveryPoint;
use app\models\User;
use app\services\AttachmentService;
use app\services\chat\ChatConstructorService;
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

class OrderController extends ClientController
{
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
        $behaviours['verbFilter']['actions']['calculate-price'] = ['get'];
        array_unshift($behaviours['access']['rules'], [
            'actions' => ['create', 'update', 'cancel'],
            'allow' => false,
            'matchCallback' => fn () => !User::getIdentity()->is_verified,
        ]);
        $behaviours['access']['denyCallback'] = static function () {
            Yii::$app->response->data = ApiResponse::byResponseCode(
                ResponseCodes::getStatic()->NO_ACCESS_FOR_NOT_VERIFIED,
            );
        };

        return $behaviours;
    }

    public function actionCreate()
    {
        $user = User::getIdentity();

        $request = Yii::$app->request;
        $apiCodes = Order::apiCodes();
        $images = UploadedFile::getInstancesByName('images');
        $repeatOrderId = $request->post('repeat_order_id');
        $repeatImagesToKeep = $request->post('repeat_images_to_keep');
        $fulfillmentId = $request->post('fulfillment_id');
        $transaction = null;
        $typeDeliveryPointId = $request->post('type_delivery_point_id');

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
            $order->type_delivery_point_id = $typeDeliveryPointId;
            $order->expected_price_per_item = RateService::putInUserCurrency(
                $request->post('expected_price_per_item', 0),
            );

            if (
                (int) $typeDeliveryPointId ===
                TypeDeliveryPoint::TYPE_FULFILLMENT
            ) {
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

            $availableTypeIdsDeliveries = TypeDeliveryService::getTypeDeliveryIdsBySubcategory(
                $request->post('subcategory_id'),
            );

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
            $orderSave = SaveModelService::loadValidateAndSave(
                $order,
                [
                    'product_id',
                    'product_name',
                    'product_description',
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
                return $orderSave->apiResponse;
            }

            if ($order->product_id) {
                $buyerId = $order->product->buyer_id;
                $distributionStatus = OrderDistributionService::createDistributionTask(
                    $order->id,
                    $buyerId,
                );

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

                $conversationManager = ChatConstructorService::createChatOrder(
                    Chat::GROUP_CLIENT_BUYER,
                    [$user->id, $buyerId],
                    $order->id,
                );

                if (!$conversationManager->success) {
                    return ApiResponse::transactionCodeErrors(
                        $transaction,
                        $apiCodes->ERROR_SAVE,
                        $conversationManager->reason,
                    );
                }

                $conversationManagerBuyer = ChatConstructorService::createChatOrder(
                    Chat::GROUP_MANAGER_BUYER,
                    [$order->manager_id, $buyerId],
                    $order->id,
                );

                if (!$conversationManagerBuyer->success) {
                    $transaction?->rollBack();
                    return ApiResponse::codeErrors(
                        $apiCodes->ERROR_SAVE,
                        $conversationManagerBuyer->reason,
                    );
                }
            } else {
                $distributionStatus = OrderDistributionService::createDistributionTask(
                    $order->id,
                );

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
                            'orderLinkAttachments' => fn ($q) => $q->where([
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

            //Twilio service start
            $conversationManager = ChatConstructorService::createChatOrder(
                Chat::GROUP_CLIENT_MANAGER,
                [$user->id, $randomManager->id],
                $order->id,
            );

            NotificationConstructor::orderOrderCreated(
                $order->manager_id,
                $order->id,
            );

            // code block where return error
            if (!$conversationManager->success) {
                $transaction?->rollBack();
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $conversationManager->reason,
                );
            }
            //Twilio service end

            $transaction?->commit();

            if ($orderSave->success) {
                return ApiResponse::byResponseCode(null, [
                    'info' => OrderOutputService::getEntity($order->id),
                    'message' => 'Order created successfully',
                ]);
            } else {
                return ApiResponse::codeErrors(
                    $apiCodes->ERROR_SAVE,
                    $orderSave->reason
                );
            }
        } catch (Throwable $e) {
            $transaction?->rollBack();
            return ApiResponse::internalError($e);
        }
    }

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
            OrderOutputService::getCollection($orderIds->column()),
        );
    }

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
            OrderOutputService::getCollection($orderIds->column()),
        );
    }

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

    public function actionCalculatePrice(
        float $product_price,
        int $product_quantity,
        float $product_width,
        float $product_height,
        float $product_depth,
        float $product_weight,
        int $packaging_quantity,
        int $type_delivery_id,
        int $type_packaging_id,
        string $calculation_type,
    ) {
        return ApiResponse::info([
            'price' => OrderPriceService::outputOrderPricesInUserCurrency(
                OrderPriceService::calculateAbstractOrderPrices(
                    RateService::putInUserCurrency($product_price),
                    $product_quantity,
                    $product_width,
                    $product_height,
                    $product_depth,
                    $product_weight,
                    $packaging_quantity,
                    $type_delivery_id,
                    $type_packaging_id,
                    0,
                    0,
                    $calculation_type,
                ),
            ),
        ]);
    }
}
