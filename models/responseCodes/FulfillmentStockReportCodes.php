<?php

namespace app\models\responseCodes;

use app\components\response\ResponseCodesInfo;
use app\components\response\ResponseCodesModels;

class FulfillmentStockReportCodes extends ResponseCodesModels
{
    public ResponseCodesInfo|array $DUPLICATE_ENTRY_FULFILLMENT_STOCK_REPORT = [
        2101,
        400,
    ];
}
