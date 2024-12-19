<?php

namespace app\services\modificators\price;

use app\services\price\OrderPriceService;
use app\models\Order;
use app\services\UserActionLogService as Log;
use app\models\TypePackaging;
use Throwable;
use app\models\TypeDeliveryPrice;
use app\services\RateService;

class OrderPrice extends OrderPriceService
{
    /**
     * OrderPriceService Class
     * Methods:
     * 
     * - calculateOrderPrices(int $orderId): array
     *   Calculates the prices for a given order based on the order ID.
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

    /**
     * Calculates the prices for a given order
     * 
     * @param int $orderId Order ID
     * @return array Calculated prices
     */
    public static function calculateOrderPrices(int $orderId): array
    {
        $output = self::defaultOutput();
        try {
            $order = Order::findOne($orderId);
            if (!$order) {
                Log::danger('Order not found');
                return self::defaultOutput();
            }

            $buyerOffers = $order->buyerOffers;
            $lastOffer = array_pop($buyerOffers);
            $product = $order->product;
            $fulfillmentOffer = $order->fulfillmentOffer;
            $buyerDeliveryOffer = $order->buyerDeliveryOffer;

            if (empty($lastOffer)) {
                return self::defaultOutput();
            }

            $params = self::prepareOrderParams($order, $lastOffer, $product, $fulfillmentOffer, $buyerDeliveryOffer);
            Log::info('OrderPrice::calculateOrderPrices params: ' . json_encode($params));
            return self::calcOrderPrices($params);
        } catch (Throwable $th) {
            Log::danger(self::logError('OrderPrice::calculateOrderPrices', $th));
            return self::defaultOutput();
        }
    }

    private static function prepareOrderParams(Order $order, $lastOffer, $product, $fulfillmentOffer, $buyerDeliveryOffer): array
    {
        $userCurrency = \Yii::$app->user->getIdentity()->getSettings()->currency;
        $orderCurrency = $order->currency;

        Log::warning('LastOffer: ' . json_encode($lastOffer?->price_product));
        Log::warning('Order: ' . json_encode($order->expected_price_per_item));
        // Конвертируем все цены в валюту пользователя
        $productPrice = $lastOffer?->price_product ?? $order->expected_price_per_item;
        $productPrice = RateService::convertValue($productPrice, $lastOffer?->currency ?? $orderCurrency, $userCurrency);
        Log::warning('ProductPrice: ' . json_encode($productPrice));

        $productInspectionPrice = $lastOffer?->price_inspection ?: 0;
        $productInspectionPrice = RateService::convertValue($productInspectionPrice, $lastOffer?->currency ?? $orderCurrency, $userCurrency);

        $fulfillmentPrice = $fulfillmentOffer?->overall_price ?: 0;
        $fulfillmentPrice = RateService::convertValue($fulfillmentPrice, $lastOffer?->currency ?? $orderCurrency, $userCurrency);

        $response = [
            'orderId' => $order->id,
            'productPrice' => $productPrice,
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
            'productInspectionPrice' => $productInspectionPrice,
            'fulfillmentPrice' => $fulfillmentPrice,
            'calculationType' => $buyerDeliveryOffer ? self::TYPE_CALCULATION_PACKAGING : self::TYPE_CALCULATION_PRODUCT,
        ];
        Log::info('OrderPrice::prepareOrderParams response: ' . json_encode($response));
        return $response;
    }

    private static function prepareOrderParamsForFacade(
        float $productPrice,
        int $productQuantity,
        float $productWidth,
        float $productHeight,
        float $productDepth,
        float $productWeight,
        int $packagingQuantity,
        int $typeDeliveryId,
        int $typePackagingId,
        string $calculationType,
    ): array {
        return [
            'productPrice' => $productPrice,
            'productQuantity' => $productQuantity,
            'productDimensions' => [
                'width' => $productWidth,
                'height' => $productHeight,
                'depth' => $productDepth,
                'weight' => $productWeight,
            ],
            'packagingQuantity' => $packagingQuantity,
            'typeDeliveryId' => $typeDeliveryId,
            'typePackagingId' => $typePackagingId,
            'productInspectionPrice' => 0,
            'fulfillmentPrice' => 0,
            'calculationType' => $calculationType,
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

    private static function calcOrderPrices(array $params): array
    {
        $currency = \Yii::$app->user->getIdentity()->getSettings()->currency;
        $orderId = $params['orderId'] ?? null;
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
        $out['product_overall'] = round(
            $out['product']['overall'],
            self::SYMBOLS_AFTER_DECIMAL_POINT
        );

        return $out;
    }

    private static function calcPackagingPrice(int $typePackagingId, int $packagingQuantity): float
    {
        try {
            $userCurrency = \Yii::$app->user->getIdentity()->getSettings()->currency;
            $typePackaging = TypePackaging::findOne(['id' => $typePackagingId]);
            $price = $typePackaging?->price ?? 0;

            // Конвертируем цену упаковки в валюту пользователя
            $price = RateService::convertValue($price, 'USD', $userCurrency);

            return round($price * $packagingQuantity, self::SYMBOLS_AFTER_DECIMAL_POINT);
        } catch (Throwable $th) {
            Log::danger('error in OrderPrice::calcPackagingPrice: ' . $th->getMessage());
            return 0;
        }
    }

    private static function calcDeliveryPrice(array $dimensions, int $itemsCount, int $typeDeliveryId): float
    {
        try {

            $volumeCm3 = $dimensions['width'] * $dimensions['height'] * $dimensions['depth']; // Объем в см³
            $volumeM3 = $volumeCm3 / 1000000; // Объем в м³
            $weightPerItemKg = $dimensions['weight']; // Вес в кг
            $density = $weightPerItemKg / $volumeM3; // Плотность в кг/м³

            // init variables
            $deliveryPrice = 0;

            if ($density > 100) {
                $totalWeight = $itemsCount * $weightPerItemKg; // Убираем упаковку
                $deliveryPrice = self::getPriceByWeight($typeDeliveryId, $density) * $totalWeight; // Стоимость доставки в $
            } else {
                $deliveryPrice = ($volumeM3 * $itemsCount) * self::getPriceByVolume($typeDeliveryId);
            }
            return (float) $deliveryPrice;
        } catch (Throwable $th) {
            Log::danger('error in OrderPrice::calcDeliveryPrice: ' . $th->getMessage());
            return self::defaultOutput();
        }
    }

    public static function getPriceByWeight(int $typeDeliveryId, float $density): float
    {
        try {
            $userCurrency = \Yii::$app->user->getIdentity()->getSettings()->currency;
            $price = TypeDeliveryPrice::find()
                ->where(['type_delivery_id' => $typeDeliveryId])
                ->one();

            if (!$price) {
                $price = TypeDeliveryPrice::find()
                    ->where(['type_delivery_id' => $typeDeliveryId])
                    ->one();
            }

            // Конвертируем цену доставки в валюту пользователя
            return RateService::convertValue($price?->price ?? 0, 'USD', $userCurrency);
        } catch (Throwable $th) {
            Log::danger('error in OrderPrice::getPriceByWeight: ' . $th->getMessage());
            return 0;
        }
    }

    private static function getPriceByVolume(int $typeDeliveryId): float
    {
        try {
            $userCurrency = \Yii::$app->user->getIdentity()->getSettings()->currency;
            $price = TypeDeliveryPrice::find()
                ->where(['type_delivery_id' => $typeDeliveryId])
                ->one();

            // Конвертируем цену доставки в валюту пользователя
            return RateService::convertValue($price?->price ?? 0, 'USD', $userCurrency);
        } catch (Throwable $th) {
            Log::danger('error in OrderPrice::getPriceByVolume: ' . $th->getMessage());
            return 0;
        }
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
            'product_overall' => 0
        ];
    }

    public static function calculatorFacade(
        float $productPrice,
        int $productQuantity,
        float $productWidth,
        float $productHeight,
        float $productDepth,
        float $productWeight,
        int $packagingQuantity,
        int $typeDeliveryId,
        int $typePackagingId,
        string $calculationType,
    ): array {
        try {
            $params = self::prepareOrderParamsForFacade(
                $productPrice,
                $productQuantity,
                $productWidth,
                $productHeight,
                $productDepth,
                $productWeight,
                $packagingQuantity,
                $typeDeliveryId,
                $typePackagingId,
                $calculationType,
            );

            return self::calcOrderPrices($params);
        } catch (Throwable $th) {
            Log::danger('error in OrderPrice::calculatorFacade: ' . $th->getMessage());
            return self::defaultOutput();
        }
    }
}
