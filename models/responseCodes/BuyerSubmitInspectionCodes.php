<?php

namespace app\models\responseCodes;

use app\components\response\ResponseCodesInfo;
use app\components\response\ResponseCodesModels;

class BuyerSubmitInspectionCodes extends ResponseCodesModels
{
    public ResponseCodesInfo|array $DUPLICATE_ENTRY_BUYER_SUBMIT_INSPECTION = [
        2101,
        400,
    ];
}
