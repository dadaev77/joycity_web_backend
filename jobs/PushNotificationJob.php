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
    public $title;
    public $body;

    public function execute($queue)
    {
        try {
            $send = PushService::sendPushNotification($this->user_id, [
                'title' => $this->title,
                'body' => $this->body,
            ]);
            if (!$send) {
                Yii::error('Error sending push notification: ' . $send);
            }
            echo 'Push notification sent successfully to user ' . $this->user_id;
        } catch (Exception $e) {
            Yii::error('Error sending push notification: ' . $e->getMessage());
        }
    }
}
