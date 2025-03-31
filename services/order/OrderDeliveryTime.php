<?php

namespace app\services\order;

use app\models\Order;

class OrderDeliveryTime
{
    public static function startDelivery(Order $order)
    {
        $order->status = Order::STATUS_GROUP_ORDER_ACTIVE;
        $order->save();
    }

    public static function endDelivery(Order $order)
    {
        $order->status = Order::STATUS_GROUP_ORDER_COMPLETED;
        $order->save();
    }

    public static function calculateDeliveryTime(Order $order)
    {
        $order->delivery_time = $order->delivery_time + 1;
        $order->save();
    }
}
