<?php

namespace app\services;

use app\models\FeedbackBuyer;
use app\models\FeedbackProduct;
use app\models\Order;

class FeedbackService
{
    public static function canCreateFeedbackBuyer(
        int $buyerId,
        int $userId,
    ): bool {
        $hasOrderCompleted = Order::find()
            ->where([
                'buyer_id' => $buyerId,
                'created_by' => $userId,
                'status' => Order::STATUS_COMPLETED,
            ])
            ->exists();

        $feedback = FeedbackBuyer::find()
            ->where([
                'buyer_id' => $buyerId,
                'created_by' => $userId,
            ])

            ->exists();
        return $hasOrderCompleted && !$feedback;
    }

    public static function canCreateFeedbackProduct(
        int $productId,
        int $userId,
    ): bool {
        $hasOrderCompleted = Order::find()
            ->where([
                'product_id' => $productId,
                'created_by' => $userId,
                'status' => Order::STATUS_COMPLETED,
            ])
            ->exists();

        $feedback = FeedbackProduct::find()
            ->where([
                'product_id' => $productId,
                'created_by' => $userId,
            ])
            ->exists();

        return $hasOrderCompleted && !$feedback;
    }
}
