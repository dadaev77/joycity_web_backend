<?php

namespace app\services;

use app\components\responseFunction\Result;
use app\models\OrderTracking;

class OrderTrackingConstructorService
{
    public static function buyerAwaiting(int $orderId)
    {
        $existingOrderTracking = OrderTracking::findOne([
            'order_id' => $orderId,
            'type' => OrderTracking::STATUS_AWAITING,
        ]);

        if ($existingOrderTracking) {
            return Result::notValid([
                'errors' => [
                    'type' => 'Ошибка: Тип трекера уже существует',
                ],
            ]);
        }

        return self::createOrderTracking(
            OrderTracking::STATUS_AWAITING,
            $orderId,
        );
    }

    protected static function createOrderTracking(string $type, int $orderId)
    {
        $orderTracking = new OrderTracking();
        $orderTracking->created_at = date('Y-m-d H:i:s');
        $orderTracking->order_id = $orderId;
        $orderTracking->type = $type;

        if (!$orderTracking->save()) {
            return Result::error([
                'errors' => $orderTracking->getFirstErrors(),
            ]);
        }

        return Result::success($orderTracking);
    }

    public static function inBayerWarehouse(int $orderId)
    {
        $existingOrderTracking = OrderTracking::findOne([
            'order_id' => $orderId,
            'type' => OrderTracking::STATUS_IN_BAYER_WAREHOUSE,
        ]);

        if ($existingOrderTracking) {
            return Result::notValid([
                'errors' => [
                    'type' => 'Ошибка: Тип трекера уже существует',
                ],
            ]);
        }

        return self::createOrderTracking(
            OrderTracking::STATUS_IN_BAYER_WAREHOUSE,
            $orderId,
        );
    }
    public static function inFulfillmentWarehouse(int $orderId)
    {
        $existingOrderTracking = OrderTracking::findOne([
            'order_id' => $orderId,
            'type' => OrderTracking::STATUS_IN_FULFILLMENT_WAREHOUSE,
        ]);

        if ($existingOrderTracking) {
            return Result::notValid([
                'errors' => [
                    'type' => 'Ошибка: Тип трекера уже существует',
                ],
            ]);
        }

        return self::createOrderTracking(
            OrderTracking::STATUS_IN_FULFILLMENT_WAREHOUSE,
            $orderId,
        );
    }

    public static function sentDestination(int $orderId)
    {
        $existingOrderTracking = OrderTracking::findOne([
            'order_id' => $orderId,
            'type' => OrderTracking::STATUS_SENT_TO_DESTINATION,
        ]);

        if ($existingOrderTracking) {
            return Result::notValid([
                'errors' => [
                    'type' => 'Ошибка: Тип трекера уже существует',
                ],
            ]);
        }

        return self::createOrderTracking(
            OrderTracking::STATUS_SENT_TO_DESTINATION,
            $orderId,
        );
    }

    public static function itemArrived(int $orderId)
    {
        $existingOrderTracking = OrderTracking::findOne([
            'order_id' => $orderId,
            'type' => OrderTracking::STATUS_ARRIVED,
        ]);

        if ($existingOrderTracking) {
            return Result::notValid([
                'errors' => [
                    'type' => 'Ошибка: Тип трекера уже существует',
                ],
            ]);
        }

        return self::createOrderTracking(
            OrderTracking::STATUS_ARRIVED,
            $orderId,
        );
    }

    public static function fullyDeliveredToMarketplace(int $orderId)
    {
        $existingOrderTracking = OrderTracking::findOne([
            'order_id' => $orderId,
            'type' => OrderTracking::STATUS_FULLY_SHIPPED_MARKETPLACE,
        ]);

        if ($existingOrderTracking) {
            return Result::notValid([
                'errors' => [
                    'type' => 'Ошибка: Тип трекера уже существует',
                ],
            ]);
        }

        return self::createOrderTracking(
            OrderTracking::STATUS_FULLY_SHIPPED_MARKETPLACE,
            $orderId,
        );
    }

    public static function partiallyDeliveredToMarketplace(int $orderId)
    {
        $existingOrderTracking = OrderTracking::findOne([
            'order_id' => $orderId,
            'type' => OrderTracking::STATUS_PARTIALLY_DELIVERED_TO_MARKETPLACE,
        ]);

        if ($existingOrderTracking) {
            return Result::notValid([
                'errors' => [
                    'type' => 'Ошибка: Тип трекера уже существует',
                ],
            ]);
        }

        return self::createOrderTracking(
            OrderTracking::STATUS_PARTIALLY_DELIVERED_TO_MARKETPLACE,
            $orderId,
        );
    }

    public static function redyTransferringToMarketplace(int $orderId)
    {
        $existingOrderTracking = OrderTracking::findOne([
            'order_id' => $orderId,
            'type' => OrderTracking::STATUS_READY_TRANSFERRING_TO_MARKETPLACE,
        ]);

        if ($existingOrderTracking) {
            return Result::notValid([
                'errors' => [
                    'type' => 'Ошибка: Тип трекера уже существует',
                ],
            ]);
        }

        return self::createOrderTracking(
            OrderTracking::STATUS_READY_TRANSFERRING_TO_MARKETPLACE,
            $orderId,
        );
    }
}
