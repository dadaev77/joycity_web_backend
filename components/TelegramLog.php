<?php

namespace app\components;

use GuzzleHttp\Client;

class TelegramLog
{
    /**
     * Component for sending messages to Telegram
     * Bot name: joycity_log_bot
     * URL: APP_URL_LOG_BOT/send
     */

    // Добавить проерку окружения для отправки сообщений в телеграм чтобы урезать логи на проде

    protected $url;
    protected $client;
    private const MESSAGES_LEVEL = [
        'info',
        'warning',
        'error',
        'success',
        'critical',
        'debug',
        'alert',
    ];

    public function __construct()
    {
        $this->url = $_ENV['APP_URL_LOG_BOT'] . '/send';
        $this->client = new Client();
    }

    public function send($type, $message)
    {

        // Здесь должна быть логика отправки сообщения в Telegram
        // Например, вы можете использовать API Telegram для отправки сообщения

        $response = $this->client->post($this->url, [
            'json' => [
                'type' => $type,
                'message' => $message,
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        return $response;
    }

    public static function getMessagesLevel()
    {
        return self::MESSAGES_LEVEL;
    }
}
