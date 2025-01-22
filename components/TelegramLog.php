<?php

namespace app\components;

use GuzzleHttp\Client;

class TelegramLog
{
    private $token;
    private $chatId;
    private $client;
    protected $types = [
        'error' => ['text' => 'Ошибка', 'icon' => '🔴'],
        'info' => ['text' => 'Информация', 'icon' => '🔵'],
        'warning' => ['text' => 'Предупреждение', 'icon' => '🟡'],
        'debug' => ['text' => 'Отладка', 'icon' => '🟢'],
        'success' => ['text' => 'Успех', 'icon' => '🟢'],
    ];

    public function __construct()
    {
        $this->client = new Client();
        $this->token = $_ENV['APP_LOG_BOT_TOKEN'];
        $this->chatId = $this->getChatId($_ENV['APP_ENV']);
    }

    public function send(string $type, string $message, string $env = 'dev')
    {
        $message = $this->prepareMessage($type, $message, $env);
        $response = $this->client->request('GET', $this->getUrl($env) . $message);
        return $response->getStatusCode();
    }

    private function prepareMessage(string $type, string $message, string $env)
    {
        return "{$this->types[$type]['icon']} {$this->types[$type]['text']}\nENV [{$env}] \n\n{$message}";
    }

    private function getUrl($env)
    {
        $this->chatId = $this->getChatId($env);
        return "https://api.telegram.org/bot{$this->token}/sendMessage?chat_id={$this->chatId}&text=";
    }

    private function getChatId($env)
    {
        return $env === 'prod' ? $_ENV['APP_LOG_BOT_CHAT_ID_PROD'] : $_ENV['APP_LOG_BOT_CHAT_ID_TEST'];
    }
}
