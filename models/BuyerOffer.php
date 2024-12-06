<?php

namespace app\models;

use app\models\responseCodes\BuyerOfferCodes;
use app\models\structure\BuyerOfferStructure;
use app\services\modificators\RateService;

class BuyerOffer extends BuyerOfferStructure
{
    public const STATUS_WAITING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_DECLINED = 2;


    public function beforeSave($insert)
    {
        return parent::beforeSave($insert);
    }

    public static function getStatusMap()
    {
        return [
            ['key' => self::STATUS_WAITING, 'translate' => 'Ожидание'],
            ['key' => self::STATUS_APPROVED, 'translate' => 'Одобрено'],
            ['key' => self::STATUS_DECLINED, 'translate' => 'Отклонено'],
        ];
    }

    public static function apiCodes(): BuyerOfferCodes
    {
        return BuyerOfferCodes::getStatic();
    }

    public function rules()
    {
        return [
            [['price_product'], 'number'],
        ];
    }
}
