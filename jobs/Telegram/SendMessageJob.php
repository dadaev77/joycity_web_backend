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
    public $chatId;

    /**
     * Братан, это джобка
     * а как и че тут дальше делать, спросишь?
     * 
     * я без понятия, я только сделал шаблон
     */

    public function execute($queue)
    {
        try {
            Yii::$app->telegramLog->send($this->type, $this->message);
            echo 'success send message to telegram';
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
