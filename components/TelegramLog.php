<?php

namespace app\components;

use Yii;

class TelegramLog
{
    /**
     * @param string $type
     * @param string $message
     * @param string $threadId
     * @param string $env
     * @param bool $async
     * @return string
     */
    public function send(string $type, $message, $thread = false, string $env = 'dev', bool $async = true)
    {
        Yii::$app->queue->push(new \app\jobs\Telegram\SendMessageJob([
            'type' => $type,
            'message' => $message,
            'env' => $env,
            'async' => false,
            'thread' => $thread,
        ]));
    }

    private function sendAsync(string $type, string $message, string $threadId, string $env)
    {
        Yii::$app->queue->push(new \app\jobs\Telegram\SendMessageJob([
            'type' => $type,
            'message' => $message,
            'env' => $env,
            'async' => false,
            'thread' => $threadId,
        ]));
    }
}
