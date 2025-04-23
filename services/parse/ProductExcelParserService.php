<?php

namespace app\services\parse;

use Yii;
use Throwable;

class ProductExcelParserService
{
    public static function parse($file)
    {
        return \app\components\ApiResponse::byResponseCode(
            \app\components\response\ResponseCodes::getStatic()->BAD_REQUEST,
            [
                'message' => 'Не реализовано по причине отсутствия задач.',

            ],
            500
        );
    }
}
