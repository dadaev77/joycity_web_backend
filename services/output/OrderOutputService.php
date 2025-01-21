<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\BuyerOffer;
use app\models\Order;
use app\services\MarketplaceTransactionService;
use app\services\modificators\price\OrderPrice;
use app\services\RateService;

use app\services\SqlQueryService;
use Yii;

class OrderOutputService extends OutputService
{
    /**
     * @param int $id
     * @param bool $showDeleted
     * @param string $imageSize ['small', 'medium', 'large']
     * @return array
     * @throws Exception\InvalidParamException
     */
    public static function getEntity(int $id, $showDeleted = false, $imageSize = 'large'): array
    {
        return self::getCollection([$id], $showDeleted, $imageSize)[0];
    }

    /**
     * @param array $ids
     * @param bool $showDeleted
     * @param string $imageSize ['small', 'medium', 'large']
     * @return array
     * @throws Exception\InvalidParamException
     */
    public static function getCollection(array $ids, $showDeleted = false, $imageSize = 'large'): array
    {
        $relations = [

            'fulfillmentMarketplaceTransactions' => fn($q) => $q->orderBy(['id' => SORT_DESC]),
            'createdBy' => fn($q) => $q->select(SqlQueryService::getUserSelect())->with(['avatar']),
            'buyer' => fn($q) => $q->select(SqlQueryService::getBuyerSelect())->with(['avatar']),
            'manager' => fn($q) => $q->select(SqlQueryService::getUserSelect())->with(['avatar']),
            'buyerOffers' => fn($q) => $q->andWhere(['<>', 'status', BuyerOffer::STATUS_DECLINED])->orderBy(['status' => SORT_DESC]),
            'fulfillment' => fn($q) => $q->select(SqlQueryService::getUserSelect())->with(['avatar']),
            'fulfillmentOffer',
            'buyerDeliveryOffer',
            'deliveryPointAddress',
            'typeDelivery',
            'typeDeliveryPoint',
            'typePackaging',
            'chats',
            'subcategory',
            'product' => fn($q) => $q->select(['id', 'name_ru', 'description_ru', 'product_height', 'product_width', 'product_depth', 'product_weight'])->with(['attachments']),
            'productInspectionReports',
            'fulfillmentInspectionReport',
            'fulfillmentStockReport' => fn($q) => $q->with(['attachments']),
            'fulfillmentPackagingLabeling' => fn($q) => $q->with(['attachments']),
            'productStockReports' => fn($q) => $q->with(['attachments']),
            'orderTrackings',
            'orderRate',
        ];

        $query = Order::find()
            ->with($relations)
            ->orderBy(self::getOrderByIdExpression($ids))
            ->where(['order.id' => $ids]);

        if ($showDeleted) {
            $query->showWithDeleted();
        }

        $userCurrency = Yii::$app->user->identity->getSettings()->currency;

        return array_map(static function ($model) use ($imageSize, $userCurrency) {
            $info = ModelTypeHelper::toArray($model);
            $fulfilmentMarketplaceDeliveryInfo = MarketplaceTransactionService::getDeliveredCountInfo($info['id']);
            $info['fulfilmentMarketplaceDeliveryInfo'] = $fulfilmentMarketplaceDeliveryInfo ?: null;
            $info['productStockReport'] = $info['productStockReports'] ? $info['productStockReports'][0] : null;
            $info['buyerOffer'] = $info['buyerOffers'] ? $info['buyerOffers'][0] : null;
            $info['productInspectionReport'] = $info['productInspectionReports'] ? $info['productInspectionReports'][0] : null;
            $info['orderTracking'] = $info['orderTrackings'];

            foreach ($info['orderTracking'] as &$tracking) {
                unset($tracking['order_id']);
            }
            unset($tracking);

            // foreach ($info as $key => $value) {
            //     if ($value && (str_starts_with($key, 'price_') || $key === 'expected_price_per_item')) {
            //         $info[$key] = RateService::convertValue($value, $info['currency'], $userCurrency);
            //     }
            // }

            foreach ($info['chats'] as &$chat) {
                unset($chat['order_id'], $chat['user_verification_request_id']);
            }
            unset($chat);

            $keys = [
                'product_name_ru',
                'product_description_ru',
                'product_name_en',
                'product_description_en',
                'product_name_zh',
                'product_description_zh',
            ];

            foreach ($keys as $key) {
                unset($info[$key]);
            }

            $userLanguage = Yii::$app->user->identity->getSettings()->application_language;
            $info['product_name'] = match (strtolower($userLanguage)) {
                'ru' => $model->product_name_ru,
                'en' => $model->product_name_en,
                'zh' => $model->product_name_zh,
                default => $model->product_name_ru,
            };

            $info['product_description'] = match (strtolower($userLanguage)) {
                'ru' => $model->product_description_ru,
                'en' => $model->product_description_en,
                'zh' => $model->product_description_zh,
                default => $model->product_description_ru,
            };


            $info['attachments'] = match ($imageSize) {
                'small' => $model->attachmentsSmallSize,
                'medium' => $model->attachmentsMediumSize,
                'large' => $model->attachmentsLargeSize,
                default => $model->attachments,
            };

            if ($info['product']) {
                $info['product']['name'] = $model->product_name_ru;
                $info['product']['description'] = $model->product_description_ru;
                unset($info['product']['name_ru']);
                unset($info['product']['description_ru']);
                $info['attachments'] = array_merge(
                    $info['attachments'],
                    $info['product']['attachments'],
                );
            }

            if ($info['fulfillmentOffer'] !== null) {
                $info['fulfillmentOffer']['overall_price'] = RateService::convertValue($info['fulfillmentOffer']['overall_price'], $info['fulfillmentOffer']['currency'], $userCurrency);
            }

            if ($info['buyerOffer']) {
                $info['buyerOffer']['price_product'] = RateService::convertValue($info['buyerOffer']['price_product'], $info['buyerOffer']['currency'], $userCurrency);
                $info['buyerOffer']['price_inspection'] = RateService::convertValue($info['buyerOffer']['price_inspection'], $info['buyerOffer']['currency'], $userCurrency);
                $info['expected_price_per_item'] = RateService::convertValue($info['expected_price_per_item'], $info['currency'], $userCurrency);
            }
            if (isset($info['buyerDeliveryOffer'])) {
                $info['buyerDeliveryOffer']['price_product'] = RateService::convertValue($info['buyerDeliveryOffer']['price_product'], $info['buyerDeliveryOffer']['currency'], $userCurrency);
            }
            $info['type'] = in_array($info['status'], Order::STATUS_GROUP_ORDER, true) ? 'order' : 'request';
            $info['price'] = OrderPrice::calculateOrderPrices($info['id'], $userCurrency);

            if ($info['buyerOffer']) {
                $info['price']['overall'] = $info['buyerOffer']['price_product'] * $info['buyerOffer']['total_quantity'];
            }
            unset(
                // $info['created_at'],
                // $info['status'],
                // $info['created_by'],
                // $info['manager_id'],
                // $info['fulfillment_id'],
                // $info['product_name'],
                // $info['product_description'],
                // $info['expected_quantity'],
                // $info['expected_price_per_item'],
                // $info['expected_packaging_quantity'],
                // $info['price_product'],
                // $info['price_inspection'],
                // $info['price_packaging'],
                // $info['price_fulfilment'],
                // $info['price_delivery'],
                // $info['total_quantity'],
                // $info['is_need_deep_inspection'],
                // $info['is_deleted'],
                // $info['link_tz'],
                // $info['fulfillmentMarketplaceTransactions'],
                // $info['attachments'],
                // $info['createdBy'],
                // $info['buyer'],
                // $info['manager'],
                // $info['fulfillmentOffer'],
                // $info['buyerDeliveryOffer'],
                // $info['deliveryPointAddress'],
                // $info['typeDelivery'],
                // $info['typeDeliveryPoint'],
                // $info['typePackaging'],
                // $info['chats'],
                // $info['subcategory'],
                // $info['product'],
                // $info['productInspectionReport'],
                // $info['fulfillment'],
                // $info['fulfillmentInspectionReport'],
                // $info['fulfillmentStockReport'],
                // $info['fulfillmentPackagingLabeling'],
                // $info['productStockReports'],
                // $info['orderTrackings'],
                // $info['orderRate'],
                // $info['fulfilmentMarketplaceDeliveryInfo'],
                // $info['productStockReport'],
                // $info['buyerOffer'],
                // $info['productInspectionReport'],
                // $info['orderTracking'],
                // $info['type'],
                // 
                $info['fulfillment']['name'],
                $info['fulfillment']['surname'],
                $info['fulfillment']['description'],
                $info['orderTrackings'],
                $info['productInspectionReport']['order_id'],
                $info['productInspectionReports'],
                $info['productStockReport']['order_id'],
                $info['productStockReports'],
                $info['orderLinkAttachments'],
                $info['type_packaging_id'],
                $info['type_delivery_id'],
                $info['type_delivery_point_id'],
                $info['delivery_point_address_id'],
                $info['product_id'],
                $info['product']['productLinkAttachments'],
                $info['product']['attachments'],
                $info['subcategory_id'],
                $info['buyer']['avatar_id'],
                $info['manager']['avatar_id'],
                $info['createdBy']['avatar_id'],
                $info['buyer_id'],
                $info['buyerOffers'],
                $info['productStockReport']['productStockReportLinkAttachments'],
                $info['fulfillmentPackagingLabeling']['packagingReportLinkAttachments'],
                $info['fulfillmentStockReport']['fulfillmentStockReportLinkAttachments'],
            );

            return $info;
        }, $query->all());
    }
}
