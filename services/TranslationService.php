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
        $aiSevice = $_ENV['APP_URL_AI'];
        $path = '/translate_product_attributes';
        $url = $aiSevice . $path;

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'product_name' => $productName,
                'product_description' => $productDescription
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $response = str_replace('```json', '', $response);
            $response = str_replace('```', '', $response);
            $responseParsed = json_decode($response, true);

            if ($httpCode !== 200 || !$responseParsed['success']) {
                return Result::error();
            }

            return Result::success($responseParsed);
            
        } catch (Throwable $e) {
            return Result::error();
        }
    }
}
