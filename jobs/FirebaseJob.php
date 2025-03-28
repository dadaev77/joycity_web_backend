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
        FirebaseService::sendPushNotification($this->pushToken, $this->message, $this->os);
    }
}
