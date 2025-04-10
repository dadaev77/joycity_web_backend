<?php

namespace app\services\order;

use app\models\Order;
use app\models\OrderTracking;
use app\models\TypeDelivery;

class OrderDeliveryTimeService
{
    /**
     * Рассчитывает оставшееся время доставки в днях
     * @param Order $order
     * @return int|null
     */
    public static function calculateDeliveryTime(Order $order): ?int
    {
        // Получаем запись о статусе отправки товара
        $sentTracking = OrderTracking::find()
            ->where([
                'order_id' => $order->id, 
                'type' => OrderTracking::STATUS_SENT_TO_DESTINATION
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        // Если товар еще не отправлен, возвращаем null
        if (!$sentTracking) {
            return null;
        }

        // Получаем стандартное время доставки для типа доставки
        $typeDelivery = TypeDelivery::findOne($order->type_delivery_id);
        if (!$typeDelivery || !$typeDelivery->delivery_time_days) {
            return null;
        }

        // Вычисляем прошедшие дни с момента отправки
        $sentDate = new \DateTime($sentTracking->created_at);
        $currentDate = new \DateTime();
        $daysPassed = $currentDate->diff($sentDate)->days;

        // Вычисляем оставшиеся дни
        $remainingDays = $typeDelivery->delivery_time_days - $daysPassed;

        return $remainingDays;
    }

    /**
     * Получает стандартное время доставки для типа доставки
     * @param int $typeDeliveryId
     * @return int|null
     */
    public static function getStandardDeliveryTime(int $typeDeliveryId): ?int
    {
        $typeDelivery = TypeDelivery::findOne($typeDeliveryId);
        if (!$typeDelivery) {
            return null;
        }

        return $typeDelivery->delivery_time_days;
    }
} 