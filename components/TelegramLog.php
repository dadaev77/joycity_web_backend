<?php

namespace app\components;

use GuzzleHttp\Client;
use Yii;

class TelegramLog
{
    private $token;
    private $chatId;
    private $client;
    private $threadId;

    protected $types = [
        'error' => ['text' => 'Ошибка', 'icon' => '❌'],
        'info' => ['text' => 'Информация', 'icon' => '🔵'],
        'warning' => ['text' => 'Предупреждение', 'icon' => '🟡'],
        'debug' => ['text' => 'Отладка', 'icon' => '🟢'],
        'success' => ['text' => 'Успех', 'icon' => '✅'],
    ];

    protected $envTypes = [
        'dev' => 'Тестовый контур',
        'prod' => 'Продакшн контур',
        'both' => 'Оба контура',
    ];

    protected $threadTypes = [
        'manager' => 7,
        'fulfillment' => 9,
        'buyer' => 11,
        'client' => 3,
    ];

    public function __construct()
    {
        $this->client = new Client();
        $this->token = $_ENV['APP_LOG_BOT_TOKEN'];
        $this->chatId = $this->getChatId($_ENV['APP_ENV']);
    }

    public function send(string $type, string $message, string $threadId = null, string $env = null, bool $async = true)
    {
        $env = $env ?? $_ENV['APP_ENV'];
        
        if ($threadId && isset($this->threadTypes[$threadId])) {
            $this->threadId = $this->threadTypes[$threadId];
        }
        
        if ($async) {
            $this->sendAsync($type, $message, $threadId, $env);
            return 'Queued';
        } else {
            return $this->sendSync($type, $message, $threadId, $env);
        }
    }

    private function sendAsync(string $type, string $message, string $threadId = null, string $env)
    {
        Yii::$app->queue->push(new \app\jobs\Telegram\SendMessageJob([
            'type' => $type,
            'message' => $message,
            'env' => $env,
            'async' => false,
            'thread' => $threadId ? $this->threadTypes[$threadId] : null,
        ]));
    }

    private function sendSync(string $type, string $message, string $threadId = null, string $env)
    {
        $formattedMessage = $this->prepareMessage($type, $message, $env);

        if ($env === 'both') {
            $responseProd = $this->client->request('GET', $this->getUrl('prod', $threadId !== null) . $formattedMessage);
            $responseDev = $this->client->request('GET', $this->getUrl('dev', $threadId !== null) . $formattedMessage);
            return [
                'prod' => $responseProd->getStatusCode(),
                'dev' => $responseDev->getStatusCode(),
            ];
        }
        
        $useThread = $threadId !== null;
        $response = $this->client->request('GET', $this->getUrl($env, $useThread) . $formattedMessage);
        return $response->getStatusCode();
    }

    private function prepareMessage(string $type, string $message, string $env)
    {
        return "{$this->types[$type]['icon']} {$this->types[$type]['text']}\nENV [{$env}] \n\n{$message}";
    }

    private function getUrl($env, bool $thread = false)
    {
        $this->chatId = $this->getChatId($env);
        if ($thread && $this->threadId) {
            return "https://api.telegram.org/bot{$this->token}/sendMessage?chat_id={$this->chatId}&message_thread_id={$this->threadId}&text=";
        }
      
        return "https://api.telegram.org/bot{$this->token}/sendMessage?chat_id={$this->chatId}&text=";
    }

    private function getChatId($env)
    {
        return $env === 'prod' ? $_ENV['APP_LOG_BOT_CHAT_ID_PROD'] : $_ENV['APP_LOG_BOT_CHAT_ID_TEST'];
    }
}
