<?php

namespace app\components;

class TelegramLog
{
    /**
     * Component for sending messages to Telegram
     * Bot name: joycity_log_bot
     * URL: APP_URL_LOG_BOT/send
     */
    protected $url;

    public function __construct()
    {
        $this->url = $_ENV['APP_URL_LOG_BOT'] . '/send';
    }

    public function send($type, $message)
    {
        // Здесь должна быть логика отправки сообщения в Telegram
        // Например, вы можете использовать API Telegram для отправки сообщения

        $response = Http::post($this->url, [
            'type' => $type,
            'message' => $message,
        ]);

        return $response;
    }
}
