<?php

namespace app\services;

use app\jobs\WebsocketNotificationJob;
use Yii;

class WebsocketService
{
    /**
     * Отправка уведомления
     * @param array $participants
     * @param array $notification
     * @return string
     */
    public static function sendNotification($participants, $notification, bool $multiple = true)
    {
        return self::sendNotificationAsync($participants, $notification, $multiple);
    }

    private static function sendNotificationAsync($participants, $notification, bool $multiple = true)
    {
        $jobData = [
            'participants' => $participants,
            'notification' => $notification,
            'multiple' => $multiple
        ];

        try {
            Yii::$app->queue->push(new WebsocketNotificationJob([
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
}
