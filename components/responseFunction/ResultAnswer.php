<?php

namespace app\components\responseFunction;

class ResultAnswer
{
    public int $code;
    public mixed $result;
    public ?array $reason;
    public bool $success;

    public bool $isError;
    public bool $isNotValid;
    public bool $isNotFound;

    public function __construct(int $code, mixed $payload, array $reason = null)
    {
        $this->code = $code;
        $this->result = $payload;
        $this->success = !$code;
        $this->reason = $reason;

        $this->isError = $code === Result::ERROR;
        $this->isNotValid = $code === Result::NOT_VALID;
        $this->isNotFound = $code === Result::NOT_FOUND;
    }
}
