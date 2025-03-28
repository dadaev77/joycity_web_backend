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
            $user = \app\models\User::findOne($this->user_id);
            echo "\n" . "\033[38;5;214m" . "[PT Count]  {$this->user_id}: " . count($user->pushTokens) . "\n" . "\033[0m";
            foreach ($user->pushTokens as $pushToken) {
                echo "\n" . "\033[31m" . "--[PT:{$this->user_id}] " . $pushToken->push_token . "\033[0m";
                echo "\n" . "\033[38;5;214m" . "   [PT:OS] " . $pushToken->operating_system . "\033[0m";
                echo "\n" . "\033[38;5;214m" . "   [PT:USER] " . $user->id . "\033[0m";
                echo "\n" . "\033[38;5;214m" . "   [PT:MESSAGE] " . $this->message . "\033[0m";

                if ($pushToken->operating_system === 'ios') {
                    $pushToken->badge_count++;
                    $pushToken->save();
                }
                \app\services\push\FirebaseService::sendPushNotification(
                    $this->user_id,
                    $this->message,
                    $pushToken->push_token,
                    $pushToken->operating_system
                );
            }
        } catch (\Exception $e) {
            Yii::error("Push notification error: " . $e->getMessage(), 'push');
            return $e->getMessage();
        }
    }
}
