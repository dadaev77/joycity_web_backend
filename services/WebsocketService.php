<?php

namespace app\services;

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
        foreach ($participants as $participant) {
            Yii::$app->queue->push(new \app\jobs\WebsocketNotificationJob([
                'participants' => $participants,
                'notification' => $notification,
                'multiple' => $multiple
            ]));
        }
        return true;
    }
}
