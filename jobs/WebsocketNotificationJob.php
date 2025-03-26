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
        $this->client = new Client();
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
            throw $e;
        }
    }

    private function sendSingleNotification()
    {
        try {
            $this->client->post($_ENV['APP_URL_NOTIFICATIONS'] . '/notification/send', [
                'json' => ['notification' => $this->notification],
                'headers' => ['Content-Type' => 'application/json']
            ]);
            echo "Уведомление отправлено" . PHP_EOL;
        } catch (Exception $e) {
            Yii::error("Ошибка при отправке уведомления: " . $e->getMessage(), 'websocket');
            echo "Ошибка при отправке уведомления: " . $e->getMessage() . PHP_EOL;
        }
    }

    private function sendToParticipants()
    {
        $results = [];

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
        }
    }
}
