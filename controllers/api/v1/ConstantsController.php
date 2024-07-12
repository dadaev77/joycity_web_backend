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

    public function actionTypeDelivery()
    {
        return ApiResponse::collection(
            TypeDeliveryOutputService::getCollection(
                TypeDelivery::find()->column(),
            ),
        );
    }

    public function actionTypeDeliveryBySubcategory(int $subcategory_id)
    {
        $typeDeliveryIds = TypeDeliveryService::getTypeDeliveryIdsBySubcategory(
            $subcategory_id,
        );

        return ApiResponse::collection(
            TypeDeliveryOutputService::getCollection($typeDeliveryIds),
        );
    }

    public function actionTypeDeliveryPoint()
    {
        return ApiResponse::collection(TypeDeliveryPoint::find()->all());
    }

    public function actionTypePackaging()
    {
        return ApiResponse::collection(TypePackaging::find()->all());
    }

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

    public function actionCategory()
    {
        return ApiResponse::collection(
            CategoryOutputService::getCollection(
                Category::find()
                    ->select(['id'])
                    ->column(),
            ),
        );
    }

    public function actionSubcategory($category_id = null)
    {
        $query = Subcategory::find()->select(['id', 'category_id']);

        if ($category_id) {
            $query->where(['category_id' => $category_id]);
        }

        return ApiResponse::collection(
            SubcategoryOutputService::getCollection($query->column()),
        );
    }

    public function actionOrderStatusAll()
    {
        return ApiResponse::collection(
            Order::getStatusMap(Order::STATUS_GROUP_ALL),
        );
    }

    public function actionOrderStatusRequest()
    {
        return ApiResponse::collection(
            Order::getStatusMap(Order::STATUS_GROUP_REQUEST),
        );
    }

    public function actionFulfillmentOfferStatus()
    {
        return ApiResponse::collection(
            FulfillmentOffer::getStatusMap(FulfillmentOffer::STATUS_All),
        );
    }

    public function actionOrderStatusOrder()
    {
        return ApiResponse::collection(
            Order::getStatusMap(Order::STATUS_GROUP_ORDER),
        );
    }

    public function actionBuyerOfferStatus()
    {
        return ApiResponse::collection(BuyerOffer::getStatusMap());
    }
    public function actionFeedbackUserStatus()
    {
        return ApiResponse::collection(FeedbackUser::getStatusMap());
    }

    public function actionOrderTrackingType()
    {
        return ApiResponse::collection(OrderTracking::getStatusMap());
    }

    public function actionPackageState()
    {
        return ApiResponse::collection(ProductInspectionReport::getStatusMap());
    }

    public function actionChatGroups()
    {
        return ApiResponse::collection(Chat::getGroupsMap());
    }

    public function actionChatTypes()
    {
        return ApiResponse::collection(Chat::gettypesmap());
    }

    public function actionNotificationEntityTypes()
    {
        return ApiResponse::collection(Notification::getEntityTypeMap());
    }

    public function actionNotificationEvents()
    {
        return ApiResponse::collection(Notification::getEventMap());
    }

    public function actionFulfillmentMarketplaceTransactionStatuses()
    {
        return ApiResponse::collection(
            FulfillmentMarketplaceTransaction::getStatusMap(),
        );
    }

    public function actionTodayRate()
    {
        $apiCodes = Rate::apiCodes();
        $latestRate = Rate::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$latestRate) {
            return ApiResponse::code($apiCodes->NOT_FOUND);
        }

        $rubToCnyRate = RateService::convertRUBtoCNY(1);
        $cnyToRubRate = RateService::convertCNYtoRUB(1);
        $rubToUsd = RateService::convertRUBtoUSD(1);
        $usdToRub = RateService::convertUSDtoRUB(1);
        $cnyToUsd = RateService::convertCNYtoUSD(1);
        $usdToCny = RateService::convertUSDtoCNY(1);

        $result = [
            'date' => $latestRate->created_at,
            'RUB-CNY' => $rubToCnyRate,
            'CNY-RUB' => $cnyToRubRate,
            'RUB-USD' => $rubToUsd,
            'USD-RUB' => $usdToRub,
            'CNY-USD' => $cnyToUsd,
            'USD-CNY' => $usdToCny,
        ];

        return ApiResponse::code($apiCodes->SUCCESS, $result);
    }

    public function actionBuyerDeliveryOfferStatuses()
    {
        return ApiResponse::collection(BuyerDeliveryOffer::getStatusMap());
    }
}
