<?php

namespace app\services;

use app\components\responseFunction\Result;
use linslin\yii2\curl\Curl;

class TranslationService
{
    public static function translate(string $originalText)
    {
        $apiUrl = $_ENV['APP_URL_AI'] . '/translate_message';
        $curl = new Curl();

        $response = $curl
            ->setHeader('Content-Type', 'application/json')
            ->setRawPostData(json_encode(['original_message' => $originalText]))
            ->post($apiUrl);

        $responseParsed = json_decode($response, true);

        if (!$response || !$responseParsed['success']) {
            return Result::error();
        }

        return Result::success($responseParsed['result']);
    }

    public static function translateProductAttributes(string $productName, string $productDescription)
    {
        $apiUrl = $_ENV['APP_URL_AI'] . '/translate_product_attributes';
        $curl = new Curl();

        $response = $curl
            ->setHeader('Content-Type', 'application/json')
            ->setRawPostData(json_encode([
                'product_name' => $productName,
                'product_description' => $productDescription,
            ]))
            ->post($apiUrl);

        $responseParsed = json_decode($response, true);

        if (!$response || !$responseParsed['success']) {
            return Result::error();
        }

        return Result::success($responseParsed['result']);
    }
}
