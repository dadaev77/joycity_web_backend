<?php

namespace app\services;

use app\jobs\WebsocketNotificationJob;
use GuzzleHttp\Client;
use Yii;

class WebsocketService
{
    /**
     * Отправка уведомления
     * @param array $participants
     * @param array $notification
     * @return string
     */
    public static function sendNotification($participants, $notification, bool $multiple = true, bool $async = true)
    {
        if ($async) {
            return self::sendNotificationAsync($participants, $notification, $multiple);
        } else {
            return self::sendNotificationSync($participants, $notification);
        }
    }

    private static function sendNotificationAsync($participants, $notification, bool $multiple = true)
    {

        try {
            Yii::$app->queue->priority(10)->push(new WebsocketNotificationJob([
                'participants' => $participants,
                'notification' => $notification,
                'multiple' => $multiple
            ]));
        } catch (\Exception $e) {
            Yii::error("Ошибка при отправке уведомления: " . $e->getMessage(), 'websocket');
            return 'error ' . $e->getMessage();
        }

        return true;
    }

    private static function sendNotificationSync($participants, $notification)
    {
        $client = new Client();
        echo "Sending notifications to " . count($participants) . " participants" . PHP_EOL;
        foreach ($participants as $participant) {
            $notificationData = $notification;
            $notificationData['user_id'] = $participant;
            $client->post($_ENV['APP_URL_NOTIFICATIONS'] . '/notification/send', [
                'json' => [
                    'notification' => $notificationData,
                ],
                'headers' => ['Content-Type' => 'application/json']
            ]);
            echo "\n\033[34m[WS] Переводы успешно отправлены для пользователя: " . $participant . "\033[0m";
        }
    }
}
