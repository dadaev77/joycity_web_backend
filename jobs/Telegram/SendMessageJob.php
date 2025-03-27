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
            echo "\n" . "\033[32m" . '[Telegram] Отправлено в телеграм' . "\033[0m \n";
            var_dump([
                'type' => $this->type,
                'message' => $this->message,
                'env' => $this->env,
                'async' => 'queue',
            ]);
            echo "\n" . "\033[32m" . '[Telegram] Конец сообщения' . "\033[0m";
        } catch (Exception $e) {
            echo "\n" . "\033[31m" . '[Telegram] Ошибка отправки в телеграм: ' . $e->getMessage() . "\033[0m";
            throw $e;
        }
    }
}
