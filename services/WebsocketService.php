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
    public static function sendNotification(array $participants = [], array $notification, bool $multiple = true)
    {
        return self::sendNotificationAsync($participants, $notification, $multiple);
    }

    private static function sendNotificationAsync(array $participants, array $notification, bool $multiple = true)
    {
        $cleanNotification = array_filter($notification, function ($value) {
            return !($value instanceof \Closure);
        });
        Yii::info("Отправка уведомления: " . json_encode($cleanNotification), 'websocket');
        try {
            Yii::$app->queue->push(new WebsocketNotificationJob([
                'participants' => $participants,
                'notification' => $cleanNotification,
                'multiple' => $multiple
            ]));
        } catch (\Exception $e) {
            Yii::error("Ошибка при отправке уведомления: " . $e->getMessage(), 'websocket');
            return 'error ' . $e->getMessage();
        }

        return true;
    }
}
