<?php

namespace app\components\response;

class ResponseCodesModels extends ResponseCodes
{
    public ResponseCodesInfo|array $ERROR_SAVE = [2001, 500];
    public ResponseCodesInfo|array $ERROR_DELETE = [2002, 500];
    public ResponseCodesInfo|array $NOT_VALID = [2003, 422];
    public ResponseCodesInfo|array $NOT_FOUND = [2004, 404];
}
