<?php

namespace app\components\responseFunction;

class Result
{
    public const SUCCESS = 0;
    public const ERROR = 1;
    public const NOT_VALID = 2;
    public const NOT_FOUND = 3;
    public const SKIP = 4;

    public static function success(mixed $result = null): ResultAnswer
    {
        return new ResultAnswer(self::SUCCESS, $result);
    }

    public static function error(array $reason = null): ResultAnswer
    {
        return new ResultAnswer(self::ERROR, null, $reason);
    }

    public static function errors(array $errors): ResultAnswer
    {
        return new ResultAnswer(self::ERROR, null, ['errors' => $errors]);
    }

    public static function notValid(array $reason = null): ResultAnswer
    {
        return new ResultAnswer(self::NOT_VALID, null, $reason);
    }

    public static function notFound(array $reason = null): ResultAnswer
    {
        return new ResultAnswer(self::NOT_FOUND, null, $reason);
    }

    public static function skip(array $reason = null): ResultAnswer
    {
        return new ResultAnswer(self::SKIP, null, $reason);
    }
}
