<?php

namespace app\components;

use app\components\response\ResponseCodes;
use app\components\response\ResponseCodesInfo;
use Throwable;
use Yii;
use yii\base\Exception as BaseException;
use yii\db\Exception as DatabaseException;
use yii\db\Transaction;
use yii\web\Response;

class ApiResponse extends Response
{
    public static function handleErrors(Response $response)
    {
        $apiCodes = ResponseCodes::getSelf();
        $exception = Yii::$app->errorHandler->exception;

        if (!$response->isSuccessful && YII_ENV_PROD) {
            if (isset($response->data['codeKey'])) {
                //
            } elseif ($response->statusCode === 403) {
                $response->data = self::byResponseCode($apiCodes->NO_ACCESS);
            } elseif ($response->statusCode === 404) {
                $response->data = self::byResponseCode(
                    $apiCodes->URL_NOT_FOUND,
                );
            } elseif ($response->statusCode === 405) {
                $response->data = self::byResponseCode(
                    $apiCodes->METHOD_NOT_ALLOWED,
                );
            } elseif ($exception instanceof DatabaseException) {
                $response->data = self::byResponseCode(
                    $apiCodes->DATABASE_ERROR,
                );
            } elseif ($exception instanceof BaseException) {
                $response->data = self::byResponseCode(
                    $apiCodes->INTERNAL_ERROR,
                );
            } else {
                $response->data = self::byResponseCode(
                    $apiCodes->INTERNAL_ERROR,
                );
            }
        }
    }

    /**
     * @deprecated
     * Use code/codeInfo/codeCollection/codeErrors instead
     */
    public static function byResponseCode(
        ResponseCodesInfo $responseCode = null,
        array|null $response = null,
        int $statusCode = 0,
    ): array {
        $responseCodeInfo =
            $responseCode ?: ResponseCodes::getStatic()->SUCCESS;
        $statusCodeReal = $statusCode ?: $responseCodeInfo->statusCode;

        Yii::$app->response->setStatusCode($statusCodeReal);

        return [
            'statusCode' => $statusCodeReal,
            'response' => $response,
            'code' => $responseCodeInfo->code,
            'codeKey' => $responseCodeInfo->key,
            'message' => '',
            'success' => $statusCodeReal === 200,
        ];
    }

    /**
     * @deprecated
     * Use info instead
     */
    public static function codeInfo(
        ResponseCodesInfo $responseCode = null,
        array|null $info = null,
        int $statusCode = 0,
    ) {
        return self::byResponseCode(
            $responseCode,
            ['info' => $info],
            $statusCode,
        );
    }

    /**
     * @deprecated
     * Use collection instead
     */
    public static function codeCollection(
        ResponseCodesInfo $responseCode = null,
        array|null $collection = null,
        int $statusCode = 0,
    ) {
        return self::byResponseCode(
            $responseCode,
            ['collection' => $collection],
            $statusCode,
        );
    }

    public static function info(array|null $info = null)
    {
        return self::byResponseCode(ResponseCodes::getStatic()->SUCCESS, [
            'info' => $info,
        ]);
    }

    public static function collection(array|null $collection = null)
    {
        return self::byResponseCode(ResponseCodes::getStatic()->SUCCESS, [
            'collection' => $collection,
        ]);
    }

    public static function codeErrors(
        ResponseCodesInfo $responseCode = null,
        array|null $errors = null,
        int $statusCode = 0,
    ) {
        return self::byResponseCode(
            $responseCode,
            ['errors' => $errors['errors'] ?? $errors],
            $statusCode,
        );
    }

    public static function transactionCodeErrors(
        Transaction|null $transaction,
        ResponseCodesInfo $code,
        array|null $errors = null,
        int $statusCode = 0,
    ) {
        try {
            $transaction?->rollBack();
        } catch (Throwable) {
            // todo handle
        }

        return self::codeErrors($code, $errors, $statusCode);
    }

    public static function code(
        ResponseCodesInfo $responseCode = null,
        array|null $response = null,
        int $statusCode = 0,
    ) {
        return self::byResponseCode($responseCode, $response, $statusCode);
    }

    public static function transactionCode(
        Transaction|null $transaction,
        ResponseCodesInfo $responseCode = null,
        array|null $response = null,
        int $statusCode = 0,
    ) {
        try {
            $transaction?->rollBack();
        } catch (Throwable) {
            // todo handle
        }

        return self::code($responseCode, $response, $statusCode);
    }

    public static function internalError(
        Throwable|BaseException $e = null,
    ): array {
        $payload = null;

        if ($e && defined('YII_ENV') && YII_ENV == YII_ENV_DEV) {
            $payload =
                $e->getFile() .
                '::' .
                $e->getLine() .
                ' - ' .
                $e->getMessage() .
                ' ';
        }

        return self::code(
            ResponseCodes::getStatic()->INTERNAL_ERROR,
            $payload
                ? [
                    'errors' => ['base' => $payload],
                ]
                : null,
        );
    }
}
