<?php

namespace app\services;

use app\models\PushDevice;
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
        // Реализация отправки через Firebase
    }

    private function sendApnsNotification($token, $title, $message, $data)
    {
        // Реализация отправки через APNS
    }
} 