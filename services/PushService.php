<?php

namespace app\services;

use app\models\PushDevice;
use app\models\PushNotificationType;
use Yii;
use yii\base\Component;

class PushService extends Component
{
    /**
     * Регистрация или обновление устройства
     */
    public function registerDevice($userId, $clientId, $deviceId, $pushToken = null, $platform = null)
    {
        $device = PushDevice::find()
            ->where(['device_id' => $deviceId])
            ->one() ?? new PushDevice();

        $device->user_id = $userId;
        $device->client_id = $clientId;
        $device->device_id = $deviceId;
        $device->push_token = $pushToken;
        $device->platform = $platform;
        $device->last_active_at = new \yii\db\Expression('NOW()');

        if (!$device->save()) {
            Yii::error('Ошибка сохранения устройства: ' . json_encode($device->errors));
            throw new \Exception('Ошибка сохранения устройства');
        }

        return $device;
    }

    /**
     * Обновление push-токена
     */
    public function updatePushToken($deviceId, $pushToken)
    {
        $device = PushDevice::findOne(['device_id' => $deviceId]);
        if (!$device) {
            throw new \Exception('Устройство не найдено');
        }

        $device->push_token = $pushToken;
        $device->save();

        return $device;
    }

    /**
     * Получение устройств пользователя
     */
    public function getUserDevices($userId)
    {
        return PushDevice::find()
            ->where(['user_id' => $userId])
            ->all();
    }

    /**
     * Удаление устройства
     */
    public function removeDevice($deviceId)
    {
        $device = PushDevice::findOne(['device_id' => $deviceId]);
        if ($device) {
            $device->delete();
        }
    }

    public function sendNotification($userId, $title, $message, $data = [])
    {
        $devices = $this->getUserDevices($userId);
        foreach ($devices as $device) {
            if ($device->push_token) {
                try {
                    if ($device->platform === 'android') {
                        $this->sendFirebaseNotification($device->push_token, $title, $message, $data);
                    } elseif ($device->platform === 'ios') {
                        $this->sendApnsNotification($device->push_token, $title, $message, $data);
                    }
                } catch (\Exception $e) {
                    Yii::error("Ошибка отправки уведомления: " . $e->getMessage());
                }
            }
        }
    }

    private function sendFirebaseNotification($token, $title, $message, $data)
    {
        // Для тестирования просто логируем
        Yii::info("Firebase notification: Token: $token, Title: $title, Message: $message, Data: " . json_encode($data));
        
        // Реальная отправка будет выглядеть примерно так:
        /*
        $client = new \GuzzleHttp\Client();
        $response = $client->post('https://fcm.googleapis.com/fcm/send', [
            'headers' => [
                'Authorization' => 'key=' . YOUR_SERVER_KEY,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                ],
                'data' => $data,
            ],
        ]);
        */
    }

    private function sendApnsNotification($token, $title, $message, $data)
    {
        // Для тестирования просто логируем
        Yii::info("APNS notification: Token: $token, Title: $title, Message: $message, Data: " . json_encode($data));
    }

    /**
     * Отправка уведомления по типу
     */
    public function sendNotificationByType($userId, $typeCode, $params = [], $additionalData = [])
    {
        $type = PushNotificationType::findOne(['code' => $typeCode]);
        if (!$type) {
            throw new \Exception("Неизвестный тип уведомления: {$typeCode}");
        }

        // Формируем сообщение на основе шаблона
        $message = $this->prepareMessage($type->template, $params);
        
        // Определяем заголовок в зависимости от типа
        $title = $type->name;

        // Добавляем тип уведомления в данные
        $data = array_merge($additionalData, [
            'type' => $typeCode,
            'params' => $params
        ]);

        return $this->sendNotification($userId, $title, $message, $data);
    }

    /**
     * Отправка уведомления о новом сообщении в чате
     */
    public function sendChatMessageNotification($userId, $senderId, $messageText)
    {
        return $this->sendNotificationByType('new_chat_message', [
            'sender' => $senderId,
            'message' => $messageText
        ]);
    }

    /**
     * Отправка уведомления о новой заявке
     */
    public function sendNewRequestNotification($userId, $requestId)
    {
        return $this->sendNotificationByType('new_request', [
            'request_id' => $requestId
        ]);
    }

    /**
     * Отправка уведомления об изменении статуса
     */
    public function sendStatusChangeNotification($userId, $requestId, $status)
    {
        return $this->sendNotificationByType('status_changed', [
            'request_id' => $requestId,
            'status' => $status
        ]);
    }

    /**
     * Подготовка сообщения на основе шаблона
     */
    private function prepareMessage($template, $params)
    {
        return strtr($template, array_map(function($value) {
            return '{' . $value . '}';
        }, $params));
    }

    /**
     * Получение устройств по роли пользователя
     */
    public function getUserDevicesByRole($role)
    {
        return PushDevice::find()
            ->joinWith('user')
            ->where(['user.role' => $role])
            ->all();
    }
} 