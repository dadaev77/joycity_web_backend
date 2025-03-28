<?php

namespace app\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use Exception;
use Yii;
use app\services\push\FirebaseService;

class FirebaseJob extends BaseObject implements JobInterface
{
    public $message;
    public $pushToken;
    public $os;

    public function execute($queue)
    {
        echo "\n" . "\033[38;5;214m" . "************************************************" . "\033[0m";
        echo "\n" . "\033[38;5;214m" . "   [FJ:PUSH_TOKEN] " . $this->pushToken . "\033[0m";
        echo "\n" . "\033[38;5;214m" . "   [FJ:OS] " . $this->os . "\033[0m";
        echo "\n" . "\033[38;5;214m" . "************************************************" . "\033[0m";
        FirebaseService::sendPushNotification($this->pushToken, $this->message, $this->os);
    }
}
