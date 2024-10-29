<?php

namespace app\services\price;

use app\helpers\ArrayHelperExtended;
use app\models\Order;
use app\services\RateService;
use Throwable;
use app\services\UserActionLogService as LogService;

class OrderPriceService extends PriceOutputService
{
    public static function calculateOrderPrices(int $orderId): array
    {
        \app\services\UserActionLogService::info('orderId: ' . $orderId . ' in calculateOrderPrices');
        try {
            $order = Order::findOne(['id' => $orderId]);
            if (!$order) {
                \app\services\UserActionLogService::danger('order not found');
                return self::getPricesConfig();
            }
            $buyerOffers = $order->buyerOffers;
            $buyerOffer = array_pop($buyerOffers);
            $buyerDeliveryOffer = $order->buyerDeliveryOffer;
            $fulfillmentOffer = $order->fulfillmentOffer;
            $lastOffer = $buyerDeliveryOffer ?: $buyerOffer;
            $product = $order->product;

            return self::calculateAbstractOrderPrices(
                $order->id, // TODO: remove after testing
                $lastOffer?->price_product ?: $order->expected_price_per_item,
                $lastOffer?->total_quantity ?: $order->expected_quantity,
                $lastOffer?->product_width ?: ($product?->product_width ?: 0),
                $lastOffer?->product_height ?: ($product?->product_height ?: 0),
                $lastOffer?->product_depth ?: ($product?->product_depth ?: 0),
                $lastOffer?->product_weight ?: ($product?->product_weight ?: 0),
                $buyerDeliveryOffer?->total_packaging_quantity ?:
                    $order->expected_packaging_quantity,
                $order->type_delivery_id,
                $order->type_packaging_id,
                $buyerOffer?->price_inspection ?: 0,
                $fulfillmentOffer?->overall_price ?: 0,
                $buyerDeliveryOffer
                    ? self::TYPE_CALCULATION_PACKAGING
                    : self::TYPE_CALCULATION_PRODUCT,
            );
        } catch (Throwable $e) {
            \app\services\UserActionLogService::danger('error in calculateOrderPrices: ' . json_encode($e->getMessage()));
            return self::getPricesConfig();
        }
    }

    public static function calculateAbstractOrderPrices(
        int $orderId, // TODO: remove after testing
        float $productPrice,
        int $productQuantity,
        float $productWidth,
        float $productHeight,
        float $productDepth,
        float $productWeight,
        int $packagingQuantity,
        int $typeDeliveryId,
        int $typePackagingId,
        float $productInspectionPrice,
        float $fulfillmentPrice,
        string $calculationType,
    ): array {

        $out = self::getPricesConfig();
        $isTypePackaging = $calculationType === self::TYPE_CALCULATION_PACKAGING;

        \app\services\UserActionLogService::info('call method calculateDeliveryPrice in calculateAbstractOrderPrices');
        $deliveryPrice = OrderDeliveryPriceService::calculateDeliveryPrice(
            $debug = false, // TODO: remove after testing
            $orderId, // TODO: remove after testing
            $isTypePackaging ? $packagingQuantity : $productQuantity,
            $productWidth,
            $productHeight,
            $productDepth,
            $productWeight,
            $typeDeliveryId,
        );

        $packagingPrice = OrderDeliveryPriceService::calculatePackagingPrice(
            $typePackagingId,
            $packagingQuantity,
        );

        $out['delivery']['packaging'] = $packagingPrice;
        $out['delivery']['delivery'] = $deliveryPrice;
        \app\services\UserActionLogService::info('deliveryPrice: ' . $deliveryPrice);
        \app\services\UserActionLogService::info('packagingPrice: ' . $packagingPrice);

        $out['delivery']['overall'] =
            $out['delivery']['packaging'] + $out['delivery']['delivery'];

        $out['product_inspection'] = $productInspectionPrice;

        $out['fulfillment'] = RateService::convertRUBtoCNY($fulfillmentPrice);

        $out['product']['price_per_item'] = $productPrice;
        $out['product']['overall'] = round($productPrice * $productQuantity, self::SYMBOLS_AFTER_DECIMAL_POINT);
        $out['product']['cost_price_per_item'] = $productQuantity
            ? round(
                ($out['product_inspection'] +
                    $out['product']['overall'] +
                    $out['delivery']['overall'] +
                    $out['fulfillment']) /
                    $productQuantity,
                self::SYMBOLS_AFTER_DECIMAL_POINT,
            )
            : 0;

        $out['overall'] = round(
            $out['product']['overall'] +
                $out['product_inspection'] +
                $out['delivery']['overall'] +
                $out['fulfillment'],
            self::SYMBOLS_AFTER_DECIMAL_POINT,
        );
        return $out;
    }

    public static function outputOrderPricesInUserCurrency(array $prices): array
    {
        return ArrayHelperExtended::mapDeep(
            static fn($amount) => RateService::outputInUserCurrency($amount),
            $prices,
        );
    }

    public static function getPricesConfig(): array
    {
        return [
            'product' => [
                'price_per_item' => 0,
                'cost_price_per_item' => 0,
                'overall' => 0,
            ],
            'product_inspection' => 0,
            'delivery' => [
                'packaging' => 0,
                'delivery' => 0,
                'overall' => 0,
            ],
            'fulfillment' => 0,
            'overall' => 0,
        ];
    }
}
