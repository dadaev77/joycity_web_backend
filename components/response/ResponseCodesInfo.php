<?php

namespace app\components\response;

class ResponseCodesInfo
{
    public int $code;
    public string $key;
    public int $statusCode;

    public function __construct(int $code, string $key, int $statusCode)
    {
        $this->code = $code;
        $this->key = $key;
        $this->statusCode = $statusCode;
    }
}
