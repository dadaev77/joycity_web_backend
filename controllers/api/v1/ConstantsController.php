<?php

namespace app\controllers\api\v1;

use app\components\ApiResponse;
use app\controllers\api\V1Controller;
use app\models\BuyerDeliveryOffer;
use app\models\BuyerOffer;
use app\models\Category;
use app\models\Chat;
use app\models\DeliveryPointAddress;
use app\models\FeedbackUser;
use app\models\FulfillmentMarketplaceTransaction;
use app\models\FulfillmentOffer;
use app\models\Notification;
use app\models\Order;
use app\models\OrderTracking;
use app\models\ProductInspectionReport;
use app\models\Rate;
use app\models\Subcategory;
use app\models\TypeDelivery;
use app\models\TypeDeliveryPoint;
use app\models\TypePackaging;
use app\services\output\CategoryOutputService;
use app\services\output\SubcategoryOutputService;
use app\services\output\TypeDeliveryOutputService;
use app\services\RateService;
use app\services\TypeDeliveryService;

class ConstantsController extends V1Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbFilter']['actions']['type-delivery'] = ['get'];
        $behaviors['verbFilter']['actions']['type-delivery-point'] = ['get'];
        $behaviors['verbFilter']['actions']['type-packaging'] = ['get'];
        $behaviors['verbFilter']['actions']['category'] = ['get'];
        $behaviors['verbFilter']['actions']['subcategory'] = ['get'];
        $behaviors['verbFilter']['actions']['order-status-all'] = ['get'];
        $behaviors['verbFilter']['actions']['order-status-request'] = ['get'];
        $behaviors['verbFilter']['actions']['order-status-order'] = ['get'];
        $behaviors['verbFilter']['actions']['feedback-user-status'] = ['get'];
        $behaviors['verbFilter']['actions']['order-tracking-type'] = ['get'];
        $behaviors['verbFilter']['actions']['package-state'] = ['get'];
        $behaviors['verbFilter']['actions']['chat-groups'] = ['get'];
        $behaviors['verbFilter']['actions']['chat-types'] = ['get'];
        $behaviors['verbFilter']['actions']['today-rate'] = ['get'];
        $behaviors['verbFilter']['actions']['notification-entity-types'] = [
            'get',
        ];
        $behaviors['verbFilter']['actions']['notification-events'] = ['get'];
        $behaviors['verbFilter']['actions']['fulfillment-offer-status'] = [
            'get',
        ];
        $behaviors['verbFilter']['actions']['buyer-delivery-offer-statuses'] = [
            'get',
        ];
        $behaviors['verbFilter']['actions']['type-delivery-by-subcategory'] = [
            'get',
        ];

        return $behaviors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/type-delivery",
     *     summary="Получение типов доставки",
     *     description="Этот метод возвращает список всех типов доставки.",
     *     @OA\Response(
     *         response=200,
     *         description="Список типов доставки",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionTypeDelivery()
    {
        return ApiResponse::collection(
            TypeDeliveryOutputService::getCollection(
                TypeDelivery::find()->column(),
            ),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/type-delivery-by-subcategory/{subcategory_id}",
     *     summary="Получение типов доставки по подкатегории",
     *     description="Этот метод возвращает типы доставки для указанной подкатегории.",
     *     @OA\Parameter(
     *         name="subcategory_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список типов доставки для подкатегории",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionTypeDeliveryBySubcategory(int $subcategory_id)
    {
        $typeDeliveryIds = TypeDeliveryService::getTypeDeliveryIdsBySubcategory(
            $subcategory_id,
        );

        return ApiResponse::collection(
            TypeDeliveryOutputService::getCollection($typeDeliveryIds),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/type-delivery-point",
     *     summary="Получение типов точек доставки",
     *     description="Этот метод возвращает список всех типов точек доставки.",
     *     @OA\Response(
     *         response=200,
     *         description="Список типов точек доставки",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionTypeDeliveryPoint()
    {
        return ApiResponse::collection(TypeDeliveryPoint::find()->all());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/type-packaging",
     *     summary="Получение типов упаковки",
     *     description="Этот метод возвращает список всех типов упаковки.",
     *     @OA\Response(
     *         response=200,
     *         description="Список типов упаковки",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionTypePackaging()
    {
        return ApiResponse::collection(TypePackaging::find()->all());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/delivery-point-address/{type_delivery_point_id}",
     *     summary="Получение адресов точек доставки",
     *     description="Этот метод возвращает адреса точек доставки по указанному типу.",
     *     @OA\Parameter(
     *         name="type_delivery_point_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список адресов точек доставки",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionDeliveryPointAddress(int $type_delivery_point_id = 0)
    {
        $query = DeliveryPointAddress::find();

        if ($type_delivery_point_id) {
            $query->andWhere([
                'type_delivery_point_id' => $type_delivery_point_id,
            ]);
        }

        return ApiResponse::collection($query->all());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/category",
     *     summary="Получение категорий",
     *     description="Этот метод возвращает список всех категорий.",
     *     @OA\Response(
     *         response=200,
     *         description="Список категорий",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionCategory()
    {
        $rootCategoriesIds = Category::find()
            ->select(['id'])
            ->where(['parent_id' => null, 'is_deleted' => 0])
            ->column();

        $categoriesIds = Category::find()
            ->select(['id'])
            ->where(['parent_id' => $rootCategoriesIds, 'is_deleted' => 0])
            ->column();

        foreach (
            Category::find()->where(['id' => $categoriesIds])->with(['subcategories'])->all() as $category
        ) {
            if (empty($category->subcategories)) {
                array_splice($categoriesIds, array_search($category->id, $categoriesIds), 1);
            }
        }

        return ApiResponse::collection(
            CategoryOutputService::getCollection($categoriesIds)
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/subcategory/{category_id}",
     *     summary="Получение подкатегорий",
     *     description="Этот метод возвращает подкатегории для указанной категории.",
     *     @OA\Parameter(
     *         name="category_id",
     *         in="path",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список подкатегорий",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionSubcategory($category_id = null)
    {
        if ($category_id) {
            $query = Category::find()->select(['id', 'parent_id'])->where(['parent_id' => $category_id, 'is_deleted' => 0]);
        } else {
            $query = Category::find()->select(['id'])->where(['parent_id' => null, 'is_deleted' => 0]);
        }

        return ApiResponse::collection(
            SubcategoryOutputService::getCollection($query->column()),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/order-status-all",
     *     summary="Получение всех статусов заказов",
     *     description="Этот метод возвращает все статусы заказов.",
     *     @OA\Response(
     *         response=200,
     *         description="Список статусов заказов",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionOrderStatusAll()
    {
        return ApiResponse::collection(
            Order::getStatusMap(Order::STATUS_GROUP_ALL),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/order-status-request",
     *     summary="Получение статусов заказов по запросу",
     *     description="Этот метод возвращает статусы заказов по запросу.",
     *     @OA\Response(
     *         response=200,
     *         description="Список статусов заказов по запросу",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionOrderStatusRequest()
    {
        return ApiResponse::collection(
            Order::getStatusMap(Order::STATUS_GROUP_REQUEST),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/fulfillment-offer-status",
     *     summary="Получение статусов предложений выполнения",
     *     description="Этот метод возвращает статусы предложений выполнения.",
     *     @OA\Response(
     *         response=200,
     *         description="Список статусов предложений выполнения",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionFulfillmentOfferStatus()
    {
        return ApiResponse::collection(
            FulfillmentOffer::getStatusMap(FulfillmentOffer::STATUS_All),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/order-status-order",
     *     summary="Получение статусов заказов",
     *     description="Этот метод возвращает статусы заказов.",
     *     @OA\Response(
     *         response=200,
     *         description="Список статусов заказов",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionOrderStatusOrder()
    {
        return ApiResponse::collection(
            Order::getStatusMap(Order::STATUS_GROUP_ORDER),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/buyer-offer-status",
     *     summary="Получение статусов предложений покупателя",
     *     description="Этот метод возвращает статусы предложений покупателя.",
     *     @OA\Response(
     *         response=200,
     *         description="Список статусов предложений покупателя",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionBuyerOfferStatus()
    {
        return ApiResponse::collection(BuyerOffer::getStatusMap());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/feedback-user-status",
     *     summary="Получение статусов отзывов пользователей",
     *     description="Этот метод возвращает статусы отзывов пользователей.",
     *     @OA\Response(
     *         response=200,
     *         description="Список статусов отзывов пользователей",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionFeedbackUserStatus()
    {
        return ApiResponse::collection(FeedbackUser::getStatusMap());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/order-tracking-type",
     *     summary="Получение типов отслеживания заказов",
     *     description="Этот метод возвращает типы отслеживания заказов.",
     *     @OA\Response(
     *         response=200,
     *         description="Список типов отслеживания заказов",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionOrderTrackingType()
    {
        return ApiResponse::collection(OrderTracking::getStatusMap());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/package-state",
     *     summary="Получение состояний упаковки",
     *     description="Этот метод возвращает состояния упаковки.",
     *     @OA\Response(
     *         response=200,
     *         description="Список состояний упаковки",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionPackageState()
    {
        return ApiResponse::collection(ProductInspectionReport::getStatusMap());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/chat-groups",
     *     summary="Получение групп чата",
     *     description="Этот метод возвращает группы чата.",
     *     @OA\Response(
     *         response=200,
     *         description="Список групп чата",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionChatGroups()
    {
        return ApiResponse::collection(Chat::getGroupsMap());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/chat-types",
     *     summary="Получение типов чата",
     *     description="Этот метод возвращает типы чата.",
     *     @OA\Response(
     *         response=200,
     *         description="Список типов чата",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionChatTypes()
    {
        return ApiResponse::collection(Chat::gettypesmap());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/notification-entity-types",
     *     summary="Получение типов сущностей уведомлений",
     *     description="Этот метод возвращает типы сущностей уведомлений.",
     *     @OA\Response(
     *         response=200,
     *         description="Список типов сущностей уведомлений",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionNotificationEntityTypes()
    {
        return ApiResponse::collection(Notification::getEntityTypeMap());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/notification-events",
     *     summary="Получение событий уведомлений",
     *     description="Этот метод возвращает события уведомлений.",
     *     @OA\Response(
     *         response=200,
     *         description="Список событий уведомлений",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionNotificationEvents()
    {
        return ApiResponse::collection(Notification::getEventMap());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/fulfillment-marketplace-transaction-statuses",
     *     summary="Получение статусов транзакций на рынке выполнения",
     *     description="Этот метод возвращает статусы транзакций на рынке выполнения.",
     *     @OA\Response(
     *         response=200,
     *         description="Список статусов транзакций на рынке выполнения",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionFulfillmentMarketplaceTransactionStatuses()
    {
        return ApiResponse::collection(
            FulfillmentMarketplaceTransaction::getStatusMap(),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/today-rate",
     *     summary="Получение текущего курса",
     *     description="Этот метод возвращает текущий курс валют.",
     *     @OA\Response(
     *         response=200,
     *         description="Текущий курс валют",
     *         @OA\JsonContent(
     *             @OA\Property(property="date", type="string", example="2023-10-01T12:00:00Z"),
     *             @OA\Property(property="RUB-CNY", type="number", format="float", example=0.1),
     *             @OA\Property(property="CNY-RUB", type="number", format="float", example=10.0),
     *             @OA\Property(property="RUB-USD", type="number", format="float", example=0.02),
     *             @OA\Property(property="USD-RUB", type="number", format="float", example=50.0),
     *             @OA\Property(property="CNY-USD", type="number", format="float", example=0.15),
     *             @OA\Property(property="USD-CNY", type="number", format="float", example=6.67)
     *         )
     *     )
     * )
     */
    public function actionTodayRate()
    {
        $apiCodes = Rate::apiCodes();
        $latestRate = Rate::find()
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if (!$latestRate) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }
        // set sadp to 2 (SADP - SYMBOLS AFTER DECIMAL POINT)
        RateService::setSADP(2);
        $rubToCny = RateService::convertRUBtoCNY(1);
        $cnyToRub = round($latestRate['CNY'], 2); //RateService::convertCNYtoRUB(1);
        $rubToUsd = RateService::convertRUBtoUSD(1);
        $usdToRub =  round($latestRate['USD'], 2); //RateService::convertUSDtoRUB(1);
        $cnyToUsd = RateService::convertCNYtoUSD(1);
        $usdToCny = RateService::convertUSDtoCNY(1);

        $result = [
            'date' => $latestRate->created_at,
            'RUB-CNY' => $rubToCny,
            'CNY-RUB' => $cnyToRub,
            'RUB-USD' => $rubToUsd,
            'USD-RUB' => $usdToRub,
            'CNY-USD' => $cnyToUsd,
            'USD-CNY' => $usdToCny,
        ];

        return ApiResponse::code($apiCodes->SUCCESS, $result);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/constants/buyer-delivery-offer-statuses",
     *     summary="Получение статусов предложений доставки покупателя",
     *     description="Этот метод возвращает статусы предложений доставки покупателя.",
     *     @OA\Response(
     *         response=200,
     *         description="Список статусов предложений доставки покупателя",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function actionBuyerDeliveryOfferStatuses()
    {
        return ApiResponse::collection(BuyerDeliveryOffer::getStatusMap());
    }
}
