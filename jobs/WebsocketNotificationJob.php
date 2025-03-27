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
            echo "\nУведомление отправлено" . PHP_EOL;
        } catch (Exception $e) {
            Yii::error("Ошибка при отправке уведомления: " . $e->getMessage(), 'websocket');
            echo "\nОшибка при отправке уведомления: " . $e->getMessage() . PHP_EOL;
        }
    }

    private function sendToParticipants()
    {
        $this->client = new Client();

        foreach ($this->participants as $participant) {
            $notificationData = [
                'notification' => [
                    'type' => 'new_message',
                    'user_id' => $participant,
                    'data' => $this->notification,
                ],
            ];
            $this->client->post($_ENV['APP_URL_NOTIFICATIONS'] . '/notification/send', [
                'json' => $notificationData,
                'headers' => ['Content-Type' => 'application/json']
            ]);
            echo "\nУведомление отправлено для пользователя: " . $participant . PHP_EOL;
        }
    }
}
