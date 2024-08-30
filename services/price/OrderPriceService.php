<?php

namespace app\services\price;

use app\helpers\ArrayHelperExtended;
use app\models\Order;
use app\services\RateService;
use Throwable;
use app\services\UserActionLogService as LogService;

class OrderPriceService
{
    public const TYPE_CALCULATION_PRODUCT = 'product';
    public const TYPE_CALCULATION_PACKAGING = 'packaging';

    public static function calculateOrderPrices(int $orderId): array
    {
        try {
            $order = Order::findOne(['id' => $orderId]);
            if (!$order) {
                return self::getPricesConfig();
            }
            LogService::info('calculate order prices is called for order with id ' . $order->id);
            $buyerOffers = $order->buyerOffers;
            $buyerOffer = array_pop($buyerOffers);
            $buyerDeliveryOffer = $order->buyerDeliveryOffer;
            $fulfillmentOffer = $order->fulfillmentOffer;
            $lastOffer = $buyerDeliveryOffer ?: $buyerOffer;
            $product = $order->product;

            return self::calculateAbstractOrderPrices(
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
            return self::getPricesConfig();
        }
    }

    public static function calculateAbstractOrderPrices(
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
        $isTypePackaging =
            $calculationType === self::TYPE_CALCULATION_PACKAGING;

        $deliveryPrice = OrderDeliveryPriceService::calculateDeliveryPrice(
            $isTypePackaging ? $packagingQuantity : $productQuantity,
            $productWidth,
            $productHeight,
            $productDepth,
            $productWeight,
            $typeDeliveryId,
        );
        LogService::info('calculated delivery price: ' . $deliveryPrice);
        $packagingPrice = OrderDeliveryPriceService::calculatePackagingPrice(
            $typePackagingId,
            $packagingQuantity,
        );
        LogService::info('calculated packaging price: ' . $packagingPrice);
        $out['product_inspection'] = $productInspectionPrice;
        $out['fulfillment'] = RateService::convertRUBtoCNY($fulfillmentPrice);

        $out['delivery']['packaging'] = RateService::convertRUBtoUSD(
            $packagingPrice,
        );
        $out['delivery']['delivery'] = RateService::convertRUBtoUSD(
            $deliveryPrice,
        );
        $out['delivery']['overall'] =
            $out['delivery']['packaging'] + $out['delivery']['delivery'];

        $out['product']['price_per_item'] = $productPrice;
        $out['product']['overall'] = round($productPrice * $productQuantity, 4);
        $out['product']['cost_price_per_item'] = $productQuantity
            ? round(
                ($out['product_inspection'] +
                    $out['product']['overall'] +
                    $out['delivery']['overall'] +
                    $out['fulfillment']) /
                    $productQuantity,
                4,
            )
            : 0;

        $out['overall'] = round(
            $out['product']['overall'] +
                $out['product_inspection'] +
                $out['delivery']['overall'] +
                $out['fulfillment'],
            4,
        );
        LogService::info('calculated overall price: ' . implode(', ',  $out));
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
