<?php

namespace app\services\modificators\price;

use app\services\price\OrderPriceService;

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
}
