<?php

namespace app\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use app\services\push\PushService;
use Exception;
use Yii;

class PushNotificationJob extends BaseObject implements JobInterface
{
    public $user_id;
    public $message;

    public function execute($queue)
    {
        try {
            $send = PushService::sendPushNotification($this->user_id, $this->message, false);
            if (!$send) {
                Yii::error('Ошибка при отправке push уведомления: ' . $send);
            }
            echo "\n" . "\033[32m" . 'Push уведомление отправлено успешно для пользователя ' . $this->user_id . "\033[0m";
        } catch (Exception $e) {
            Yii::error('Ошибка при отправке push уведомления: ' . $e->getMessage());
            echo "\n" . "\033[31m" . 'Ошибка при отправке push уведомления: ' . $e->getMessage() . "\033[0m";
        }
    }
}
