<?php

namespace app\models\responseCodes;

use app\components\response\ResponseCodesInfo;
use app\components\response\ResponseCodesModels;

class FulfillmentOfferCodes extends ResponseCodesModels
{
    public ResponseCodesInfo|array $DUPLICATE_ENTRY_FULFILLMENT_OFFER = [
        2101,
        400,
    ];
}
