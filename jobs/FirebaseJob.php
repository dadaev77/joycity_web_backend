<?php

namespace app\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use Exception;
use Yii;

class FirebaseJob extends BaseObject implements JobInterface
{
    public $message;
    public $pushToken;

    public function execute($queue)
    {
        Yii::$app->telegramLog->send('info', "FirebaseJob: " . json_encode($this->message) . " " . json_encode($this->pushToken));
    }
}
