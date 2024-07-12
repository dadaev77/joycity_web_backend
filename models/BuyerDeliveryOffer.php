<?php

namespace app\models;

use app\models\structure\BuyerDeliveryOfferStructure;

class BuyerDeliveryOffer extends BuyerDeliveryOfferStructure
{
    public const STATUS_CREATED = 'created';
    public const STATUS_PAID = 'paid';

    public const STATUS_GROUP_ALL = [self::STATUS_CREATED, self::STATUS_PAID];

    public static function getStatusMap()
    {
        return [
            ['key' => self::STATUS_CREATED, 'translate' => 'Создано'],
            ['key' => self::STATUS_PAID, 'translate' => 'Оплачено'],
        ];
    }
}
