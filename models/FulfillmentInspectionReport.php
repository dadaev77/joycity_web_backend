<?php

namespace app\models;

use app\models\responseCodes\FulfillmentSubmitInspectionCodes;
use app\models\structure\FulfillmentInspectionReportStructure;

class FulfillmentInspectionReport extends FulfillmentInspectionReportStructure
{
    public const PACKAGING_CONDITION_GOOD = 'good';
    public const PACKAGING_CONDITION_BAD = 'bad';
    public const PACKAGING_CONDITION_NORMAL = 'normal';

    public static function getStatusMap()
    {
        return [
            ['key' => self::PACKAGING_CONDITION_GOOD, 'translate' => 'Хорошее'],
            [
                'key' => self::PACKAGING_CONDITION_NORMAL,
                'translate' => 'Нормальное',
            ],
            [
                'key' => self::PACKAGING_CONDITION_BAD,
                'translate' => 'Плохое',
            ],
        ];
    }

    public static function apiCodes(): FulfillmentSubmitInspectionCodes
    {
        return FulfillmentSubmitInspectionCodes::getStatic();
    }
}
