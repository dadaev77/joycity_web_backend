<?php

namespace app\jobs\Telegram;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use Exception;
use Yii;

class SendMessageJob extends BaseObject implements JobInterface
{
    public $type;
    public $message;
    public $env;
    public $async;

    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    public function execute($queue)
    {
        try {
            Yii::$app->telegramLog->send($this->type, $this->message, $this->env, $this->async);
            echo "Отправлено в телеграм\n";
        } catch (Exception $e) {
            echo "Ошибка отправки в телеграм: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
