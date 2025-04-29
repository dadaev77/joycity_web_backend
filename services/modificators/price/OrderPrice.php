<?php

namespace app\services\modificators\price;

use app\dto\OrderPriceParams;
use app\models\Order;
use app\services\price\OrderPriceService;
use app\services\RateService;
use Throwable;
use Yii;

class OrderPrice
{
    private const SYMBOLS_AFTER_DECIMAL_POINT = 2;
    private const TYPE_CALCULATION_PACKAGING = 'packaging';
    private const TYPE_CALCULATION_PRODUCT = 'product';
    private const BASE_CURRENCY = 'CNY';

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
            $order = Order::findOne($orderId);
            if (!$order) {
                throw new \RuntimeException("Заказ #$orderId не найден");
            }

            $params = self::prepareOrderParams($order, $currency, $role);
            return [$params];
            return self::computeOrderPrices($params, $currency);
        } catch (Throwable $e) {
            Yii::error("Ошибка расчёта цен для заказа #$orderId: {$e->getMessage()}");
            return self::defaultOutput();
        }
    }

    /**
     * Фасад для расчёта цен без привязки к заказу
     *
     * @param float $productPrice Цена за единицу
     * @param int $productQuantity Количество единиц
     * @param float $productWidth Ширина (см)
     * @param float $productHeight Высота (см)
     * @param float $productDepth Глубина (см)
     * @param float $productWeight Вес (г)
     * @param int $packagingQuantity Количество упаковок
     * @param int $typeDeliveryId ID типа доставки
     * @param int $typePackagingId ID типа упаковки
     * @param string $calculationType Тип расчёта ('packaging' или 'product')
     * @param string $currency Валюта для вывода цен
     * @return array Рассчитанные цены
     */
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
        string $currency
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
                $calculationType
            );
            return self::computeOrderPrices($params, $currency);
        } catch (Throwable $e) {
            Yii::error("Ошибка в calculatorFacade: {$e->getMessage()}");
            return self::defaultOutput();
        }
    }

    /**
     * Подготавливает параметры для расчёта цен из заказа
     *
     * @param Order $order Модель заказа
     * @param string $currency Валюта для конвертации
     * @param string $role Роль пользователя
     * @return OrderPriceParams
     */
    private static function prepareOrderParams(Order $order, string $currency, string $role): OrderPriceParams
    {
        $buyerOffers = $order->buyerOffers;
        $lastOffer = array_pop($buyerOffers);
        $product = $order->product;
        $fulfillmentOffer = $order->fulfillmentOffer;
        $buyerDeliveryOffer = $order->buyerDeliveryOffer;

        if (!$lastOffer) {
            throw new \RuntimeException("Предложение продавца не найдено для заказа #$order->id");
        }

        $markup = $role === 'client' ? Yii::$app->user->getIdentity()->markup : 0;
        $orderCurrency = $lastOffer->currency ?? $order->currency;
        $productPrice = $lastOffer->price_product ?? $order->expected_price_per_item;
        if ($markup) {
            $productPrice *= (1 + $markup / 100);
        }
        $productPrice = RateService::convertValue($productPrice, $orderCurrency, self::BASE_CURRENCY);

        return new OrderPriceParams([
            'orderId' => $order->id,
            'productPrice' => $productPrice,
            'productQuantity' => $lastOffer->total_quantity ?? $order->expected_quantity,
            'productDimensions' => [
                'width' => $lastOffer->product_width ?? $product?->product_width ?? 0.0,
                'height' => $lastOffer->product_height ?? $product?->product_height ?? 0.0,
                'depth' => $lastOffer->product_depth ?? $product?->product_depth ?? 0.0,
                'weight' => $lastOffer->product_weight ?? $product?->product_weight ?? 0.0,
            ],
            'packagingQuantity' => $buyerDeliveryOffer?->total_packaging_quantity ?? $order->expected_packaging_quantity,
            'typeDeliveryId' => $order->type_delivery_id,
            'typePackagingId' => $order->type_packaging_id,
            'productInspectionPrice' => RateService::convertValue(
                $lastOffer->price_inspection ?? 0.0,
                $orderCurrency,
                self::BASE_CURRENCY
            ),
            'fulfillmentPrice' => RateService::convertValue(
                $fulfillmentOffer?->overall_price ?? 0.0,
                $orderCurrency,
                self::BASE_CURRENCY
            ),
            'calculationType' => $buyerDeliveryOffer ? self::TYPE_CALCULATION_PACKAGING : self::TYPE_CALCULATION_PRODUCT,
        ]);
    }

    /**
     * Подготавливает параметры для фасада
     *
     * @param float $productPrice Цена за единицу
     * @param int $productQuantity Количество единиц
     * @param float $productWidth Ширина (см)
     * @param float $productHeight Высота (см)
     * @param float $productDepth Глубина (см)
     * @param float $productWeight Вес (г)
     * @param int $packagingQuantity Количество упаковок
     * @param int $typeDeliveryId ID типа доставки
     * @param int $typePackagingId ID типа упаковки
     * @param string $calculationType Тип расчёта
     * @return OrderPriceParams
     */
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
        string $calculationType
    ): OrderPriceParams {
        return new OrderPriceParams([
            'orderId' => 0,
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
            'productInspectionPrice' => 0.0,
            'fulfillmentPrice' => 0.0,
            'calculationType' => $calculationType,
        ]);
    }

    /**
     * Рассчитывает цены на основе параметров
     *
     * @param OrderPriceParams $params Параметры для расчёта
     * @param string $currency Валюта для вывода цен
     * @return array Рассчитанные цены
     */
    private static function computeOrderPrices(OrderPriceParams $params, string $currency): array
    {

        return OrderPriceService::computePrices($params, $currency);
    }

    /**
     * Возвращает конфигурацию цен по умолчанию
     *
     * @return array
     */
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
            'overall_overhead' => 0,
            'product_overall' => 0,
        ];
    }
}
