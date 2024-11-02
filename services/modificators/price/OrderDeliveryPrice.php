<?php

namespace app\services\modificators\price;

use app\services\price\OrderDeliveryPriceService;

class OrderDeliveryPrice extends OrderDeliveryPriceService
{
    /**
     * OrderDeliveryPriceService Class
     *
     * Methods:
     * 
     * - typeDeliveryPriceConfig(int $typeDeliveryId): array
     *   Retrieves the configuration for delivery prices based on the delivery type ID.
     *
     * - addPriceRangeToTypeDelivery(int $typeDeliveryId): ResultAnswer
     *   Adds price ranges for a specific delivery type, saving them to the database.
     *
     * - getPriceByWeight(int $typeDeliveryId, float $weight): float
     *   Retrieves the delivery price based on the weight of the item and the delivery type.
     *
     * - calculateProductDensity(float $widthPerItem, float $heightPerItem, float $depthPerItem, float $weightPerItem): float
     *   Calculates the density of a product based on its dimensions and weight.
     *
     * - calculateDeliveryPrice(
     *     bool $debug = false,
     *     int $orderId,
     *     int $itemsCount,
     *     float $widthPerItem,
     *     float $heightPerItem,
     *     float $depthPerItem,
     *     float $weightPerItem,
     *     int $typeDeliveryId
     *   ): mixed
     *   Calculates the delivery price based on various parameters including item dimensions and weight.
     *
     * - getPriceByVolume(int $typeDeliveryId): float
     *   Retrieves the delivery price based on the volume for a specific delivery type.
     *
     * - calculatePackagingPrice(int $typePackagingId, int $packagingQuantity): float
     *   Calculates the packaging price based on the type of packaging and quantity.
     */
}
