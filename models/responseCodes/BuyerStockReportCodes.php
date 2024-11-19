<?php

namespace app\models\responseCodes;

use app\components\response\ResponseCodesInfo;
use app\components\response\ResponseCodesModels;

class BuyerStockReportCodes extends ResponseCodesModels
{
    public ResponseCodesInfo|array $DUPLICATE_ENTRY_BUYER_STOCK_REPORT = [
        2101,
        400,
    ];
}
