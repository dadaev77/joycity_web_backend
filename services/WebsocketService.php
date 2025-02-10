<?php

namespace app\services;

use app\components\responseFunction\Result;
use GuzzleHttp\Client;

class WebsocketService
{
    public static function sendNotification(array $notification)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->post( $_ENV['APP_URL_NOTIFICATIONS'] . '/notification/send', [
            'json' => ['notification' => $notification],
            'headers' => ['Content-Type' => 'application/json']
        ]);
        \Yii::$app->telegramLog->send('success', 'Send notification');
        if ($response->getBody()->getContents() !== 'ok') {
            return Result::error();
        }

        return Result::success();
    }
}
