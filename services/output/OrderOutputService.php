<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\BuyerOffer;
use app\models\Order;
use app\services\MarketplaceTransactionService;
use app\services\modificators\price\OrderPrice;
use app\services\RateService;

use app\services\SqlQueryService;
use app\services\order\OrderDeliveryTimeService;
use Yii;

class OrderOutputService extends OutputService
{
    /**
     * @param int $id
     * @param bool $showDeleted
     * @param string $imageSize ['small', 'medium', 'large', 'xlarge']
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
     * @param string $imageSize ['small', 'medium', 'large', 'xlarge']
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
            'subcategory',
            'product' => fn($q) => $q->select(['id', 'name_ru', 'description_ru', 'product_height', 'product_width', 'product_depth', 'product_weight'])->with(['attachments']),
            'productInspectionReports',
            'fulfillmentInspectionReport',
            'fulfillmentStockReport' => fn($q) => $q->with(['attachments']),
            'fulfillmentPackagingLabeling' => fn($q) => $q->with(['attachments', 'attachments_dict']),
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

            foreach ($info as $key => $value) {
                if ($value && (str_starts_with($key, 'price_') || $key === 'expected_price_per_item')) {
                    $info[$key] = RateService::convertValue($value, $info['currency'], $userCurrency);
                }
            }

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

            if ($info['product']) {
                $product = \app\models\Product::findOne($info['product']['id']);
                $info['product']['name'] = $model->product_name_ru;
                $info['product']['description'] = $model->product_description_ru;
                unset($info['product']['name_ru']);
                unset($info['product']['description_ru']);
                $info['attachments'] = match ($imageSize) {
                    'small' => $product->attachmentsSmallSize,
                    'medium' => $product->attachmentsMediumSize,
                    'large' => $product->attachmentsLargeSize,
                    'xlarge' => $product->attachmentsXlargeSize,
                    default => $product->attachments,
                };

                $info['attachments_dict'] = [
                    '256' => $product->attachmentsSmallSize,
                    '512' => $product->attachmentsMediumSize,
                    '1024' => $product->attachmentsLargeSize,
                    '2048' => $product->attachmentsXlargeSize,
                ];
            } else {
                $info['attachments'] = match ($imageSize) {
                    'small' => $model->attachmentsSmallSize,
                    'medium' => $model->attachmentsMediumSize,
                    'large' => $model->attachmentsLargeSize,
                    'xlarge' => $model->attachmentsXlargeSize,
                    default => $model->attachments,
                };

                $info['attachments_dict'] = [
                    '256' => $model->attachmentsSmallSize,
                    '512' => $model->attachmentsMediumSize,
                    '1024' => $model->attachmentsLargeSize,
                    '2048' => $model->attachmentsXlargeSize,
                ];
            }

            if ($info['fulfillmentOffer'] !== null) {
                $info['fulfillmentOffer']['overall_price'] = RateService::convertValue($info['fulfillmentOffer']['overall_price'], $info['fulfillmentOffer']['currency'], $userCurrency);
            }

            if ($info['buyerOffer']) {
                $info['buyerOffer']['price_product'] = RateService::convertValue($info['buyerOffer']['price_product'], $info['buyerOffer']['currency'], $userCurrency);
                $info['buyerOffer']['price_inspection'] = RateService::convertValue($info['buyerOffer']['price_inspection'], $info['buyerOffer']['currency'], $userCurrency);
            }
            if (isset($info['buyerDeliveryOffer'])) {
                $info['buyerDeliveryOffer']['price_product'] = RateService::convertValue($info['buyerDeliveryOffer']['price_product'], $info['buyerDeliveryOffer']['currency'], $userCurrency);
            }
            $info['type'] = in_array($info['status'], Order::STATUS_GROUP_ORDER, true) ? 'order' : 'request';

            $info['price'] = OrderPrice::calculateOrderPrices($info['id'], $userCurrency);

            if ($info['buyerOffer']) {
                $info['price']['product_overall'] = $info['buyerOffer']['price_product'] * $info['buyerOffer']['total_quantity'];
            }

            $timeDelivery = OrderDeliveryTimeService::calculateDeliveryTime($model);
            unset($info['timeDelivery']);

            $tempInfo = [];
            foreach ($info as $key => $value) {
                $tempInfo[$key] = $value;
                if ($key === 'typeDeliveryPoint') {
                    $tempInfo['timeDelivery'] = $timeDelivery;
                }
            }
            $info = $tempInfo;

            $info['chats'] = [];

            unset(
                $info['delivery_start_date'],
                $info['delivery_days_expected'],
                $info['delivery_delay_days'],
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
