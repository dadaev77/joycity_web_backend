<?php

namespace app\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use GuzzleHttp\Client;
use Exception;
use Yii;

class WebsocketNotificationJob extends BaseObject implements JobInterface
{
    public $notification;
    public $participants;
    public $multiple;
    private $client;

    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    public function execute($queue)
    {
        try {
            if (!$this->multiple) {
                return $this->sendSingleNotification();
            }
            return $this->sendToParticipants();
        } catch (Exception $e) {
            Yii::error("Ошибка в джобе: " . $e->getMessage(), 'websocket');
            echo "\n\033[31mОшибка в джобе: " . $e->getMessage() . "\033[0m" . PHP_EOL;
            throw $e;
        }
    }

    private function sendSingleNotification()
    {
        $this->client = new Client();
        try {
            $this->client->post($_ENV['APP_URL_NOTIFICATIONS'] . '/notification/send', [
                'json' => ['notification' => $this->notification],
                'headers' => ['Content-Type' => 'application/json']
            ]);
            echo "\n\033[32mУведомление успешно отправлено\033[0m" . PHP_EOL;
        } catch (Exception $e) {
            Yii::error("Ошибка при отправке уведомления: " . $e->getMessage(), 'websocket');
            echo "\n\033[31mОшибка при отправке уведомления: " . $e->getMessage() . "\033[0m" . PHP_EOL;
        }
    }

    private function sendToParticipants()
    {
        $this->client = new Client();

        foreach ($this->participants as $participant) {
            $notificationData = $this->notification;
            $this->client->post($_ENV['APP_URL_NOTIFICATIONS'] . '/notification/send', [
                'json' => [
                    'notification' => $notificationData,
                ],
                'headers' => ['Content-Type' => 'application/json']
            ]);
            echo "\n\033[34m[WS] Уведомление успешно отправлено для пользователя: " . $participant . "\033[0m";
        }
    }
}
