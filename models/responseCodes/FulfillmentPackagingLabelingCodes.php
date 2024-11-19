<?php

namespace app\models\responseCodes;

use app\components\response\ResponseCodesInfo;
use app\components\response\ResponseCodesModels;

class FulfillmentPackagingLabelingCodes extends ResponseCodesModels
{
    public ResponseCodesInfo|array $DUPLICATE_ENTRY_FULFILLMENT_PACKAGING_LABELING = [
        2101,
        400,
    ];
}
