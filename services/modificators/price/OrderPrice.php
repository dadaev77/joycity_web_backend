<?php

namespace app\services\modificators\price;

use app\services\price\OrderPriceService;
use app\models\Order;
use app\services\UserActionLogService as Log;
use app\models\TypePackaging;
use Throwable;
use app\models\TypeDeliveryPrice;

class OrderPrice extends OrderPriceService
{
    /**
     * OrderPriceService Class
     * Methods:
     * 
     * - calculateOrderPrices(int $orderId, string $currency = 'usd'): array
     *   Calculates the prices for a given order based on the order ID and currency.
     *
     * - calculateAbstractOrderPrices(
     *     string $currency,
     *     int $orderId,
     *     float $productPrice,
     *     int $productQuantity,
     *     float $productWidth,
     *     float $productHeight,
     *     float $productDepth,
     *     float $productWeight,
     *     int $packagingQuantity,
     *     int $typeDeliveryId,
     *     int $typePackagingId,
     *     float $productInspectionPrice,
     *     float $fulfillmentPrice,
     *     string $calculationType
     *   ): array
     *   Calculates abstract order prices based on various parameters.
     *
     * - outputOrderPricesInUserCurrency(array $prices): array
     *   Converts the given prices to the user's currency.
     *
     * - getPricesConfig(): array
     *   Returns the default prices configuration.
     */

    public static function calculateOrderPrices(int $orderId, string $currency = 'usd'): array
    {
        $output = self::defaultOutput();

        try {
            $order = Order::findOne($orderId);
            $buyerOffers = $order->buyerOffers;
            $lastOffer = array_pop($buyerOffers);
            $product = $order->product;
            $fulfillmentOffer = $order->fulfillmentOffer;
            $buyerDeliveryOffer = $order->buyerDeliveryOffer;

            $params = self::prepareOrderParams($order, $lastOffer, $product, $fulfillmentOffer, $buyerDeliveryOffer);
            return self::calcOrderPrices($currency, $params);
        } catch (Throwable $th) {
            Log::danger(self::logError('OrderPrice::calculateOrderPrices', $th));
            return self::defaultOutput();
        }
    }

    private static function prepareOrderParams(Order $order, $lastOffer, $product, $fulfillmentOffer, $buyerDeliveryOffer): array
    {
        return [
            'productPrice' => $lastOffer?->price_product ?? $order->expected_price_per_item,
            'productQuantity' => $lastOffer?->total_quantity ?? $order->expected_quantity,
            'productDimensions' => [
                'width' => $lastOffer?->product_width ?? ($product?->product_width ?: 0),
                'height' => $lastOffer?->product_height ?? ($product?->product_height ?: 0),
                'depth' => $lastOffer?->product_depth ?? ($product?->product_depth ?: 0),
                'weight' => $lastOffer?->product_weight ?? ($product?->product_weight ?: 0),
            ],
            'packagingQuantity' => $buyerDeliveryOffer?->total_packaging_quantity ?? $order->expected_packaging_quantity,
            'typeDeliveryId' => $order->type_delivery_id,
            'typePackagingId' => $order->type_packaging_id,
            'productInspectionPrice' => $lastOffer?->price_inspection ?: 0,
            'fulfillmentPrice' => $fulfillmentOffer?->overall_price ?: 0,
            'calculationType' => $buyerDeliveryOffer ? self::TYPE_CALCULATION_PACKAGING : self::TYPE_CALCULATION_PRODUCT,
        ];
    }

    private static function logError(string $context, Throwable $th): string
    {
        return json_encode([
            'context' => $context,
            'message' => $th->getMessage(),
            'code' => $th->getCode(),
            'file' => $th->getFile(),
            'line' => $th->getLine(),
            'trace' => $th->getTraceAsString(),
        ]);
    }

    private static function calcOrderPrices(string $currency, array $params): array
    {
        $out = self::defaultOutput();
        $isTypePackaging = $params['calculationType'] === self::TYPE_CALCULATION_PACKAGING;

        $packagingPrice = self::calcPackagingPrice($params['typePackagingId'], $params['packagingQuantity']);
        $deliveryPrice = self::calcDeliveryPrice(
            $params['productDimensions'],
            $params['productQuantity'],
            $params['typeDeliveryId'],
        );

        // Заполнение выходных данных
        $out['delivery']['packaging'] = $packagingPrice;
        $out['delivery']['delivery'] = $deliveryPrice;
        $out['delivery']['overall'] = $packagingPrice + $deliveryPrice;
        $out['product_inspection'] = $params['productInspectionPrice'];
        $out['fulfillment'] = $params['fulfillmentPrice'];
        $out['product']['price_per_item'] = $params['productPrice'];
        $out['product']['overall'] = round($params['productPrice'] * $params['productQuantity'], self::SYMBOLS_AFTER_DECIMAL_POINT);
        $out['overall'] = round(
            $out['product']['overall'] +
                $out['product_inspection'] +
                $out['delivery']['overall'] +
                $out['fulfillment'],
            self::SYMBOLS_AFTER_DECIMAL_POINT,
        );

        return $out;
    }

    private static function calcPackagingPrice(int $typePackagingId, int $packagingQuantity): float
    {
        try {
            Log::log('call calculate packaging price');
            $typePackaging = TypePackaging::findOne(['id' => $typePackagingId]);
            return round(($typePackaging?->price ?? 0) * $packagingQuantity, self::SYMBOLS_AFTER_DECIMAL_POINT);
        } catch (Throwable $th) {
            Log::danger('error in OrderPrice::calcPackagingPrice: ' . $th->getMessage());
            return 0;
        }
    }

    private static function calcDeliveryPrice(array $dimensions, int $itemsCount, int $typeDeliveryId): float
    {
        $volumeCm3 = $dimensions['width'] * $dimensions['height'] * $dimensions['depth']; // Объем в см³
        $volumeM3 = $volumeCm3 / 1000000; // Объем в м³
        $weightPerItemKg = $dimensions['weight'] / 1000; // Вес в кг
        $density = $weightPerItemKg / $volumeM3; // Плотность в кг/м³

        // init variables
        $deliveryPrice = 0;

        if ($density > 100) {
            $totalWeight = $itemsCount * $weightPerItemKg; // Убираем упаковку
            $deliveryPrice = self::getPriceByWeight($typeDeliveryId, $density) * $totalWeight; // Стоимость доставки в $
        } else {
            $deliveryPrice = ($volumeM3 * $itemsCount) * self::getPriceByVolume($typeDeliveryId);
        }

        return $deliveryPrice;
    }

    public static function getPriceByWeight(int $typeDeliveryId, float $weight): float
    {
        $typeDeliveryPrice = TypeDeliveryPrice::find()
            ->where(['type_delivery_id' => $typeDeliveryId])
            ->andWhere(['<=', 'range_min', $weight])
            ->andWhere(['>', 'range_max', $weight])
            ->one();

        return $typeDeliveryPrice ? $typeDeliveryPrice->price : 0;
    }

    private static function getPriceByVolume(int $typeDeliveryId): float
    {
        return 350;
    }

    private static function defaultOutput(): array
    {
        return [
            'product' => [
                'price_per_item' => 0,
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
