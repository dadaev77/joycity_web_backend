<?php

namespace app\controllers;

use yii\rest\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Yii;

class HealthCheckController extends Controller
{
    private $apiUrl = 'https://api.telegram.org/bot';
    
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        return $behaviors;
    }

    public function actionChats()
    {
        return $this->asJson('Chats is ok')->setStatusCode(200);
    }

    public function actionQueue()
    {
        return $this->asJson('Queue is ok')->setStatusCode(200);
    }

    public function actionRates()
    {
        return $this->asJson('Rates is ok')->setStatusCode(200);
    }

    public function actionAzure()
    {
        return $this->asJson('Azure is ok')->setStatusCode(200);
    }

    public function actionWebSockets()
    {
        return $this->asJson('WebSockets is ok')->setStatusCode(200);
    }

    public function actionTelegram()
    {
        try {
            $botTokens = [
                'prod' => $_ENV['APP_LOG_BOT_TOKEN_PROD'] ?? null,
                'stage' => $_ENV['APP_LOG_BOT_TOKEN_STAGE'] ?? null
            ];

            if (!$botTokens['prod'] && !$botTokens['stage']) {
                return $this->asJson('Telegram is not configured')->setStatusCode(503);
            }
            
            $hasError = false;

            foreach ($botTokens as $env => $token) {
                if (!$token) {
                    continue;
                }

                try {
                    $client = new Client();
                    $url = $this->apiUrl . $token . '/getMe';
                    $response = $client->request('GET', $url);
                    $result = json_decode($response->getBody()->getContents(), true);

                    if ($result['ok'] !== true) {
                        $hasError = true;
                    }
                } catch (\Exception $e) {
                    $hasError = true;
                }
            }

            return $this->asJson('Telegram is ' . ($hasError ? 'not ok' : 'ok'))->setStatusCode($hasError ? 500 : 200);

        } catch (\Exception $e) {
            return $this->asJson('Telegram is not ok')->setStatusCode(503);
        }
    }
}
