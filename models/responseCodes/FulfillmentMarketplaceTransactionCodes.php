<?php

namespace app\models\responseCodes;

use app\components\response\ResponseCodesInfo;
use app\components\response\ResponseCodesModels;

class FulfillmentMarketplaceTransactionCodes extends ResponseCodesModels
{
    public ResponseCodesInfo|array $MARKETPLACE_DELIVERED_COUNT_MORE_THAN_ALLOWED = [
        2101,
        400,
    ];
}
