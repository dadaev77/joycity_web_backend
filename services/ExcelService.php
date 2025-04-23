<?php

namespace app\services;

use Yii;
use Throwable;
use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use yii\web\UploadedFile;

class ExcelService
{
    private static $typesOfRequests = ['order', 'product'];
    private static $allowedExtensions = ['xls', 'xlsx'];
    private static $allowedFileTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];

    public static function uploadExcel(
        UploadedFile $file,
        string $type
    ) {
        if (!in_array($type, self::$typesOfRequests)) {
            return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, [
                'message' => 'Неверный тип файла',
                'type' => $type
            ], 422);
        }

        $errors = self::validateFile($file, $type);
        if (!empty($errors)) {
            return ApiResponse::byResponseCode(ResponseCodes::getStatic()->BAD_REQUEST, [
                'message' => 'Ошибка валидации файла',
                'errors' => $errors
            ], 422);
        }

        $result = $type == 'order' ?
            \app\services\parse\OrderExcelParserService::parse($file) :
            \app\services\parse\ProductExcelParserService::parse($file);

        return $result;
    }

    private static function validateFile(UploadedFile $file, string $type)
    {
        $errors = [];

        if (!in_array($file->type, self::$allowedFileTypes)) {
            $errors[] = [
                'message' => 'Неверный тип файла',
                'type' => $type
            ];
        }

        if (!in_array($file->getExtension(), self::$allowedExtensions)) {
            $errors[] = [
                'message' => 'Неверное расширение файла',
                'type' => $type
            ];
        }

        return $errors;
    }
}
