<?php

namespace app\models\responseCodes;

use app\components\response\ResponseCodesInfo;
use app\components\response\ResponseCodesModels;

class FulfillmentSubmitInspectionCodes extends ResponseCodesModels
{
    public ResponseCodesInfo|array $DUPLICATE_ENTRY_FULFILLMENT_SUBMIT_INSPECTION = [
        2101,
        400,
    ];
}
