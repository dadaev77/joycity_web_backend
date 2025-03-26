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
        echo "\nВыполняется джоб" . PHP_EOL;
        try {
            if (!$this->multiple) {
                echo "\nОтправляется одно уведомление" . PHP_EOL;
                return $this->sendSingleNotification();
            }
            echo "\nОтправляется несколько уведомлений" . PHP_EOL;
            return $this->sendToParticipants();
        } catch (Exception $e) {
            Yii::error("Ошибка в джобе: " . $e->getMessage(), 'websocket');
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
        $message = \app\models\Message::findOne($this->notification);

        foreach ($this->participants as $participant) {
            $notificationData = [
                'notification' => [
                    'type' => 'new_message',
                    'user_id' => $participant,
                    'data' => $message->toArray(),
                ],
            ];
            echo "\nОтправляется уведомление для пользователя: " . $participant . PHP_EOL;
            $this->client->post($_ENV['APP_URL_NOTIFICATIONS'] . '/notification/send', [
                'json' => $notificationData,
                'headers' => ['Content-Type' => 'application/json']
            ]);
            echo "\nУведомление отправлено для пользователя: " . $participant . PHP_EOL;
        }
    }
}
