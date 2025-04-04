<?php

namespace app\components\response;

class ResponseCodes
{
    public ResponseCodesInfo|array $SUCCESS = [1000, 200];
    public ResponseCodesInfo|array $NOT_VALIDATED = [1001, 400];
    public ResponseCodesInfo|array $NOT_FOUND = [1002, 404];
    public ResponseCodesInfo|array $NOT_AUTHENTICATED = [1003, 401];
    public ResponseCodesInfo|array $NO_ACCESS = [1004, 403];
    public ResponseCodesInfo|array $BAD_REQUEST = [1005, 400];
    public ResponseCodesInfo|array $URL_NOT_FOUND = [1006, 404];
    public ResponseCodesInfo|array $INTERNAL_ERROR = [1007, 500];
    public ResponseCodesInfo|array $DATABASE_ERROR = [1008, 500];
    public ResponseCodesInfo|array $METHOD_NOT_ALLOWED = [1009, 405];
    public ResponseCodesInfo|array $NO_ACCESS_FOR_NOT_VERIFIED = [1011, 403];
    public ResponseCodesInfo|array $PERMISSION_NOT_FOUND = [1012, 403];
    public function __construct()
    {
        $defaults = get_class_vars(static::class);
        $skipKeys = [];

        foreach ($defaults as $key => $config) {
            if (in_array($key, $skipKeys, true)) {
                continue;
            }

            [$code, $statusCode] = $config;

            $this->$key = new ResponseCodesInfo($code, $key, $statusCode);
        }
    }

    public static function getSelf(): ResponseCodes
    {
        return new self();
    }

    public static function getStatic(): static
    {
        return new static();
    }
}
