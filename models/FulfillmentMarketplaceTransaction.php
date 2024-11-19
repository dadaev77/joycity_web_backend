<?php

namespace app\models;

use app\models\responseCodes\FulfillmentMarketplaceTransactionCodes;
use app\models\structure\FulfillmentMarketplaceTransactionStructure;

class FulfillmentMarketplaceTransaction extends
    FulfillmentMarketplaceTransactionStructure
{
    public const STATUS_CREATED = 'created';
    public const STATUS_PAID = 'paid';

    public const STATUS_All = [self::STATUS_CREATED, self::STATUS_PAID];

    public static function apiCodes(): FulfillmentMarketplaceTransactionCodes
    {
        return FulfillmentMarketplaceTransactionCodes::getStatic();
    }

    public static function getStatusMap()
    {
        return [
            ['key' => self::STATUS_CREATED, 'translate' => 'Создано'],
            ['key' => self::STATUS_PAID, 'translate' => 'Оплачено'],
        ];
    }
}
