<?php

namespace app\models;

use app\models\responseCodes\FulfillmentOfferCodes;
use app\models\structure\FulfillmentOfferStructure;

class FulfillmentOffer extends FulfillmentOfferStructure
{
    public const STATUS_CREATED = 'created';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_PAID = 'paid';

    public const STATUS_All = [
        self::STATUS_CREATED,
        self::STATUS_ACCEPTED,
        self::STATUS_PAID,
    ];

    public function beforeSave($insert)
    {
        return parent::beforeSave($insert);
    }

    public static function getStatusMap()
    {
        return [
            ['key' => self::STATUS_CREATED, 'translate' => 'Создано'],
            ['key' => self::STATUS_ACCEPTED, 'translate' => 'Принято'],
            ['key' => self::STATUS_PAID, 'translate' => 'Оплачено'],
        ];
    }

    public static function apiCodes(): FulfillmentOfferCodes
    {
        return FulfillmentOfferCodes::getStatic();
    }
}
