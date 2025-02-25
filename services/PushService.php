<?php

namespace app\services;
use app\models\PushNotification;

class PushService
{
    public static function sendPushNotification($clientId, $message)
    {
        $notifications = PushNotification::findAll(['client_id' => $clientId]);

        foreach ($notifications as $notification) {
            // Логика отправки push-уведомления
            // Например, через Firebase Cloud Messaging (FCM)
            self::sendToDevice($notification->push_token, $message);
        }
    }
    private static function sendToDevice($pushToken, $message)
    {
        // Реализация отправки уведомления на устройство
    }

    public static function notifyNewMessage($clientId)
    {
        self::sendPushNotification($clientId, 'Новое сообщение');
    }

    public static function notifyNewApplication($clientId)
    {
        self::sendPushNotification($clientId, 'Новая заявка');
    }

    public static function notifyStatusChange($clientId)
    {
        self::sendPushNotification($clientId, 'Изменение статуса заявки');
    }
}