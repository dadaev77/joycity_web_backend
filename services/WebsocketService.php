<?php

namespace app\services;

use app\components\responseFunction\Result;
use linslin\yii2\curl\Curl;

class WebsocketService
{
    public static function sendNotification(array $notification)
    {
        // todo Notification model
        $response = (new Curl())
            ->setHeader('Content-Type', 'application/json')
            ->setRawPostData(json_encode(['notification' => $notification]))
            ->post(self::getWebsocketUrl() . '/notification/send');

        if ($response !== 'ok') {
            return Result::error();
        }

        return Result::success();
    }

    public static function getWebsocketUrl()
    {
        return 'https://' . $_ENV['WEBSOCKET_CONTAINER_URL'] . '/socket.io/';
    }
}
