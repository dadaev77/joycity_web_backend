<?php

namespace app\models\responseCodes;

use app\components\response\ResponseCodesInfo;
use app\components\response\ResponseCodesModels;

class UserVerificationRequestCodes extends ResponseCodesModels
{
    public ResponseCodesInfo|array $ALREADY_VERIFIED = [2101, 400];
    public ResponseCodesInfo|array $HAS_ACTIVE_REQUEST = [2102, 400];
    public ResponseCodesInfo|array $ALREADY_APPROVED = [2103, 400];
}
