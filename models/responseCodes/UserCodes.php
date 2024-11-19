<?php

namespace app\models\responseCodes;

use app\components\response\ResponseCodesInfo;
use app\components\response\ResponseCodesModels;

class UserCodes extends ResponseCodesModels
{
    public ResponseCodesInfo|array $CREDENTIALS_NOT_FOUND = [2101, 404];
    public ResponseCodesInfo|array $CREDENTIALS_NOT_PASSED = [2102, 403];
    public ResponseCodesInfo|array $ERROR_EMAIL_SEND = [2103, 500];
    public ResponseCodesInfo|array $EMAIL_EXISTS = [2105, 409];
    public ResponseCodesInfo|array $WRONG_EMAIL_VERIFICATION_CODE = [2106, 400];
    public ResponseCodesInfo|array $WRONG_PASSWORD_RESET_VERIFICATION_CODE = [
        2107,
        400,
    ];
    public ResponseCodesInfo|array $HAS_ACTIVE_ORDER = [2108, 403];
    public ResponseCodesInfo|array $OLD_PASSWORD_DOESNT_MATCH = [2109, 400];
    public ResponseCodesInfo|array $CONFIRM_PASSWORD_DOESNT_MATCH = [2110, 400];
    public ResponseCodesInfo|array $EMAIL_NOT_EXISTS = [2111, 400];
    public ResponseCodesInfo|array $PHONE_NUMBER_EXISTS = [2112, 409];
    public ResponseCodesInfo|array $CREDENTIALS_NOT_PASSED_FOR_THIS_ROLE = [
        2113,
        400,
    ];
}
