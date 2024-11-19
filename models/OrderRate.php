<?php

namespace app\models;

use app\models\structure\OrderRateStructure;

class OrderRate extends OrderRateStructure
{
    public const TYPE_FULFILLMENT_PAYMENT = 'fulfillment_payment';
    public const TYPE_PRODUCT_PAYMENT = 'buyer_payment';
    public const TYPE_PRODUCT_DELIVERY_PAYMENT = 'buyer_delivery_payment';

    public static function getStatusMap()
    {
        return [
            [
                'key' => self::TYPE_FULFILLMENT_PAYMENT,
                'translate' => 'Оплата услуг фулфилмента',
            ],
            [
                'key' => self::TYPE_PRODUCT_PAYMENT,
                'translate' => 'Оплата товара',
            ],
            [
                'key' => self::TYPE_PRODUCT_DELIVERY_PAYMENT,
                'translate' => 'Оплата доставки',
            ],
        ];
    }
}
