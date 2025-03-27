<?php

namespace app\services;

use app\components\responseFunction\Result;

use GuzzleHttp\Client;

class TranslationService
{
    protected $async;
    protected $client;
    protected $apiUrl;

    public function __construct(bool $async = true)
    {
        $this->async = $async;
        $this->client = new Client();
        $this->apiUrl = $_ENV['APP_URL_AI'];
    }

    public static function translate(
        string $originalText,
        bool $async = true,
        array $entity = []
    ) {
        $service = new self($async);

        try {
            $response = $service->client->post($service->apiUrl . '/translate_message', [
                'json' => [
                    'original_message' => $originalText
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'verify' => false,
            ]);

            $responseParsed = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() !== 200 || !$responseParsed['success']) {
                return Result::error();
            }

            return Result::success($responseParsed['result']);
        } catch (\Throwable $e) {
            return Result::error();
        }
    }

    public static function translateProductAttributes(string $productName, string $productDescription, bool $async = true)
    {

        $service = new self($async);

        try {
            $response = $service->client->post($service->apiUrl . '/translate_product_attributes', [
                'json' => [
                    'product_name' => $productName,
                    'product_description' => $productDescription
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'verify' => false,
            ]);

            $responseParsed = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() !== 200 || !$responseParsed['success']) {
                return Result::error();
            }

            return Result::success($responseParsed['result']);
        } catch (\Throwable $e) {
            return Result::error();
        }
    }
}
