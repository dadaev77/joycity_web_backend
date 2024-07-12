<?php

namespace app\models\responseCodes;

use app\components\response\ResponseCodesInfo;
use app\components\response\ResponseCodesModels;

class OrderCodes extends ResponseCodesModels
{
    public ResponseCodesInfo|array $NO_ACCESS_MODIFY_ACCEPTED_ORDER = [
        2101,
        500,
    ];
}
