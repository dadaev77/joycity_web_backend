<?php

namespace app\jobs\Telegram;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use Exception;
use GuzzleHttp\Client;
use Yii;

class SendAlertMessageJob extends BaseObject implements JobInterface
{
    // params
    public $type;
    public $message;
    public $env;
    public $async;
    public $thread;

    // properties
    protected $types;
    protected $envTypes;
    protected $client;
    protected $token;
    protected $chatId;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->types = $this->getTypes();
        $this->envTypes = $this->getEnvTypes();
        $this->token = $this->env === 'prod'
            ? $_ENV['APP_LOG_BOT_ALERTS_PROD']
            : $_ENV['APP_LOG_BOT_ALERTS_STAGE'];
        $this->chatId = $this->env === 'prod'
            ? $_ENV['APP_LOG_BOT_CHAT_ID_ALERTS_PROD']
            : $_ENV['APP_LOG_BOT_CHAT_ID_ALERTS_STAGE'];
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

            echo "\033[33m" . "[TG ALERTS: " . strtoupper($this->env) . "|" . strtoupper($this->thread) . "] \033[32mСообщение успешно отправлено\n" . "\033[0m";
        } catch (Exception $e) {
            echo "\033[33m" . "[TG ALERTS: " . strtoupper($this->env) . "|" . strtoupper($this->thread) . "] \033[31mОшибка: " . $e->getMessage() . "\n" . "\033[0m";
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
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage?chat_id={$this->chatId}";

        if ($this->type === 'critical') {
            $url .= "&message_thread_id=3";
        }

        $url .= '&text=';

        return $url;
    }

    private function getTypes()
    {
        return [
            'critical' => ['text' => 'Критическая ошибка', 'icon' => '💀'],
            'warning' => ['text' => 'Предупреждение', 'icon' => '⚠️'],
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
}
