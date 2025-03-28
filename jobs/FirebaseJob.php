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
        echo "\n" . "\033[38;5;214m" . "************************************************" . "\033[0m";
        echo "\n" . "\033[38;5;214m" . "   [FJ:MESSAGE] " . $this->message . "\033[0m";
        echo "\n" . "\033[38;5;214m" . "   [FJ:PUSH_TOKEN] " . $this->pushToken . "\033[0m";
        echo "\n" . "\033[38;5;214m" . "************************************************" . "\033[0m";
    }
}
