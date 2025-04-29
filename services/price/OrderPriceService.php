<?php

namespace app\services\price;

use app\services\price\dto\OrderPriceParams;
use app\models\Order;
use app\services\RateService;
use Throwable;
use Yii;

class OrderPriceService extends PriceOutputService
{
    public const SYMBOLS_AFTER_DECIMAL_POINT = 2;
    public const TYPE_CALCULATION_PACKAGING = 'packaging';
    public const TYPE_CALCULATION_PRODUCT = 'product';
    public const BASE_CURRENCY = 'RUB';

    /**
     * Рассчитывает цены для заказа
     *
     * @param int $orderId ID заказа
     * @param string $currency Валюта для вывода цен
     * @param string $role Роль пользователя (например, 'client')
     * @return array Рассчитанные цены
     */
    public static function calculateOrderPrices(int $orderId, string $currency, string $role): array
    {
        try {
            $order = Order::findOne(['id' => $orderId]);
            if (!$order) {
                throw new \RuntimeException("Заказ #$orderId не найден");
            }

            $params = self::prepareParams($order, $role);
            return self::computePrices($params, $currency);
        } catch (Throwable $e) {
            Yii::error("Ошибка расчёта цен для заказа #$orderId: {$e->getMessage()}");
            return self::getPricesConfig();
        }
    }

    /**
     * Подготавливает параметры для расчёта цен
     *
     * @param Order $order Модель заказа
     * @param string $role Роль пользователя
     * @return OrderPriceParams
     */
    private static function prepareParams(Order $order, string $role): OrderPriceParams
    {
        $buyerOffers = $order->buyerOffers;
        $buyerOffer = array_pop($buyerOffers);
        $buyerDeliveryOffer = $order->buyerDeliveryOffer;
        $fulfillmentOffer = $order->fulfillmentOffer;
        $lastOffer = $buyerDeliveryOffer ?: $buyerOffer;
        $product = $order->product;

        $markup = $role === 'client' ? Yii::$app->user->getIdentity()->markup : 0;
        $productPrice = $lastOffer?->price_product ?? $order->expected_price_per_item;
        if ($markup) {
            $productPrice *= (1 + $markup / 100);
        }

        return new OrderPriceParams([
            'orderId' => $order->id,
            'productPrice' => $productPrice,
            'productQuantity' => $lastOffer?->total_quantity ?? $order->expected_quantity,
            'productDimensions' => [
                'width' => $lastOffer?->product_width ?? $product?->product_width ?? 0.0,
                'height' => $lastOffer?->product_height ?? $product?->product_height ?? 0.0,
                'depth' => $lastOffer?->product_depth ?? $product?->product_depth ?? 0.0,
                'weight' => $lastOffer?->product_weight ?? $product?->product_weight ?? 0.0,
            ],
            'packagingQuantity' => $buyerDeliveryOffer?->total_packaging_quantity ?? $order->expected_packaging_quantity,
            'typeDeliveryId' => $order->type_delivery_id,
            'typePackagingId' => $order->type_packaging_id,
            'productInspectionPrice' => $buyerOffer?->price_inspection ?? 0.0,
            'fulfillmentPrice' => $fulfillmentOffer?->overall_price ?? 0.0,
            'calculationType' => $buyerDeliveryOffer ? self::TYPE_CALCULATION_PACKAGING : self::TYPE_CALCULATION_PRODUCT,
        ]);
    }

    /**
     * Рассчитывает цены на основе параметров
     *
     * @param OrderPriceParams $params Параметры для расчёта
     * @param string $currency Валюта для вывода цен
     * @return array Рассчитанные цены
     */
    public static function computePrices(OrderPriceParams $params, string $currency): array
    {
        $out = self::getPricesConfig();

        $isTypePackaging = $params->calculationType === self::TYPE_CALCULATION_PACKAGING;
        $quantity = $isTypePackaging ? $params->packagingQuantity : $params->productQuantity;

        $packagingPrice = OrderDeliveryPriceService::calculatePackagingPrice(
            $params->typePackagingId,
            $params->packagingQuantity
        );
        $deliveryPrice = OrderDeliveryPriceService::calculateDeliveryPrice(
            $quantity,
            $params->productDimensions['width'],
            $params->productDimensions['height'],
            $params->productDimensions['depth'],
            $params->productDimensions['weight'],
            $params->typeDeliveryId
        );

        $out['delivery']['packaging'] = RateService::convertValue($packagingPrice, self::BASE_CURRENCY, $currency);
        $out['delivery']['delivery'] = RateService::convertValue($deliveryPrice, self::BASE_CURRENCY, $currency);
        $out['delivery']['overall'] = $out['delivery']['packaging'] + $out['delivery']['delivery'];

        $out['product_inspection'] = RateService::convertValue(
            $params->productInspectionPrice,
            self::BASE_CURRENCY,
            $currency
        );
        $out['fulfillment'] = RateService::convertValue(
            $params->fulfillmentPrice,
            self::BASE_CURRENCY,
            $currency
        );
        $out['product']['price_per_item'] = RateService::convertValue(
            $params->productPrice,
            self::BASE_CURRENCY,
            $currency
        );
        $out['product']['overall'] = round(
            $out['product']['price_per_item'] * $params->productQuantity,
            self::SYMBOLS_AFTER_DECIMAL_POINT
        );
        $out['product']['cost_price_per_item'] = $params->productQuantity
            ? round(
                ($out['product_inspection'] +
                    $out['product']['overall'] +
                    $out['delivery']['overall'] +
                    $out['fulfillment']) / $params->productQuantity,
                self::SYMBOLS_AFTER_DECIMAL_POINT
            )
            : 0;

        $out['overall'] = round(
            $out['product']['overall'] +
                $out['product_inspection'] +
                $out['delivery']['overall'] +
                $out['fulfillment'],
            self::SYMBOLS_AFTER_DECIMAL_POINT
        );

        return $out;
    }

    /**
     * Возвращает конфигурацию цен по умолчанию
     *
     * @return array
     */
    public static function getPricesConfig(): array
    {
        return [
            'product' => [
                'expected_price_per_item' => 0,
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
