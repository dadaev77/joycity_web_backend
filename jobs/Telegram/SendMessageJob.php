<?php

namespace app\jobs\Telegram;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use Exception;
use GuzzleHttp\Client;
use Yii;

class SendMessageJob extends BaseObject implements JobInterface
{
    // -1002255927524 stage chat id
    // params
    public $type;
    public $message;
    public $env;
    public $async;
    public $thread;

    // properties
    protected $types;
    protected $envTypes;
    protected $issetTread = false;
    protected $client;
    protected $token;
    protected $chatId;
    protected $threadId;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->types = $this->getTypes();
        $this->envTypes = $this->getEnvTypes();
        $this->token = $this->env === 'prod' ? $_ENV['APP_LOG_BOT_TOKEN_PROD'] : $_ENV['APP_LOG_BOT_TOKEN_STAGE'];
        $this->chatId = $this->getChatId($this->env);

        if ($this->thread) {
            $this->issetTread = true;
            if ($this->env === 'dev') {
                $this->threadId = $this->getThreadTypesDev($this->thread);
            } else {
                $this->threadId = $this->getThreadTypesProd($this->thread);
            }
        }
    }

    public function execute($queue)
    {
        try {
            $this->client = new Client();
            $url = $this->getUrl();

            $message = $this->prepareMessage(
                $this->type,
                $this->message,
                $this->env
            );

            $this->client->request('GET', $url . $message);

            echo "\033[33m" . "[TG: " . strtoupper($this->env) . "|" . strtoupper($this->thread) . "] \033[32mСообщение успешно отправлено\n" . "\033[0m";
        } catch (Exception $e) {
            echo "\033[33m" . "[TG: " . strtoupper($this->env) . "|" . strtoupper($this->thread) . "] \033[31mОшибка: " . $e->getMessage() . "\n" . "\033[0m";
            throw $e;
        }
    }

    private function prepareMessage(string $type, $message, string $env)
    {
        $type = $this->types[$type]['icon'] . " " . $this->types[$type]['text'] . "\n";
        $type .= "-----------------------\n";
        if (is_array($message)) {
            $message = implode("\n", $message);
        }
        $fullMessage = $type . $message;
        return $fullMessage;
    }

    private function getUrl()
    {
        $url = $this->issetTread ?
            "https://api.telegram.org/bot{$this->token}/sendMessage?chat_id={$this->chatId}&message_thread_id={$this->threadId}&text=" :
            "https://api.telegram.org/bot{$this->token}/sendMessage?chat_id={$this->chatId}&text=";

        return $url;
    }

    private function getChatId($env)
    {
        return $env === 'prod' ? $_ENV['APP_LOG_BOT_CHAT_ID_PROD'] : $_ENV['APP_LOG_BOT_CHAT_ID_TEST'];
    }

    private function getTypes()
    {
        return [
            'error' => ['text' => 'Ошибка', 'icon' => '❌'],
            'info' => ['text' => 'Информация', 'icon' => '🔵'],
            'warning' => ['text' => 'Предупреждение', 'icon' => '🟡'],
            'debug' => ['text' => 'Отладка', 'icon' => '🟢'],
            'success' => ['text' => 'Успех', 'icon' => '✅'],
        ];
    }

    private function getEnvTypes()
    {
        return [
            'dev' => 'Тестовый контур',
            'prod' => 'Продакшн контур',
            'both' => 'Оба контура',
        ];
    }

    private function getThreadTypes($env)
    {
        return $env === 'dev' ? $this->getThreadTypesDev() : $this->getThreadTypesProd();
    }

    private function getThreadTypesDev($thread)
    {
        $threads = [
            'manager' => 7,
            'fulfillment' => 9,
            'buyer' => 11,
            'client' => 3,
            'rates' => 528,
        ];

        return $threads[$thread];
    }

    private function getThreadTypesProd($thread)
    {
        $threads = [
            'manager' => 2,
            'fulfillment' => 6,
            'buyer' => 8,
            'client' => 4,
            'rates' => 187,
        ];

        return $threads[$thread];
    }

    private function sendMessage()
    {
        $url = $this->getUrl();
        $message = $this->prepareMessage();
        $this->client->request('POST', $url, [
            'json' => [
                'chat_id' => $this->chatId,
                'text' => $message,
            ],
        ]);
    }
}
