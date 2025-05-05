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
    public function send(string $type, $message, $thread = false, $env = null)
    {
        Yii::$app->queue->push(new \app\jobs\Telegram\SendMessageJob([
            'type' => $type,
            'message' => $message,
            'env' => !$env ? $_ENV['APP_ENV'] : (in_array($env, ['dev', 'prod']) ? $env : $_ENV['APP_ENV']),
            'async' => false,
            'thread' => $thread,
        ]));
    }

    /**
     * @param string $type
     * @param string $message
     * @param string $threadId
     * @param string $env
     * @param bool $async
     * @return string
     */
    public function sendAlert(string $type, $message, $thread = false, $env = null)
    {
        Yii::$app->queue->push(new \app\jobs\Telegram\SendAlertMessageJob([
            'type' => $type,
            'message' => $message,
            'env' => !$env ? $_ENV['APP_ENV'] : (in_array($env, ['dev', 'prod']) ? $env : $_ENV['APP_ENV']),
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
