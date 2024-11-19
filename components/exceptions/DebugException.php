<?php

namespace app\components;

use yii\base\Exception;

class DebugException extends Exception
{
    // You can add custom properties or methods if needed
    public function __construct($message = "Default message", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
