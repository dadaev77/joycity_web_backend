<?php

namespace app\services;

use app\models\FulfillmentMarketplaceTransaction;
use app\models\Order;
use Throwable;

class MarketplaceTransactionService
{
    public static function getDeliveredCountInfo(
        int $orderId,
        int $newTransactionCount = 0,
    ): array|false {
        try {
            $order = Order::findOne(['id' => $orderId]);

            if (!$order || $newTransactionCount < 0) {
                return false;
            }

            $buyerDeliveryOffer = $order->buyerDeliveryOffer;

            if (!$buyerDeliveryOffer) {
                return false;
            }

            $totalTransactions = FulfillmentMarketplaceTransaction::find()
                ->where(['order_id' => $orderId])
                ->sum('product_count');

            $allowedQuantity =
                $buyerDeliveryOffer->total_quantity -
                ($order->fulfillmentInspectionReport?->defects_count ?: 0);
            $remainsQuantity =
                $allowedQuantity - $totalTransactions - $newTransactionCount;

            return [
                'all' => $allowedQuantity,
                'delivered' => $totalTransactions + $newTransactionCount,
                'remains' => $remainsQuantity,
                'allowed' => $remainsQuantity >= 0,
                'full' => $remainsQuantity === 0,
            ];
        } catch (Throwable $e) {
            return false;
        }
    }
}
