<?php

namespace app\commands;

use Yii;
use yii\console\Controller;

class PushController extends Controller
{
    public function actionTest($userId)
    {
        // Тест уведомления о новом сообщении
        Yii::$app->pushService->sendChatMessageNotification(
            $userId,
            'Test Sender',
            'Тестовое сообщение'
        );

        // Тест уведомления о новой заявке
        Yii::$app->pushService->sendNewRequestNotification(
            $userId,
            'REQ-123'
        );

        // Тест уведомления о смене статуса
        Yii::$app->pushService->sendStatusChangeNotification(
            $userId,
            'REQ-123',
            'В обработке'
        );

        echo "Тестовые уведомления отправлены\n";
    }

    public function actionTestRegister($userId)
    {
        try {
            $device = Yii::$app->pushService->registerDevice(
                $userId,
                'test_client',
                'test_device_' . time(),
                'fcm_test_token_' . time(),
                'android'
            );
            
            echo "Тестовое устройство зарегистрировано:\n";
            echo "ID: {$device->id}\n";
            echo "Device ID: {$device->device_id}\n";
            echo "Push Token: {$device->push_token}\n";
        } catch (\Exception $e) {
            echo "Ошибка: " . $e->getMessage() . "\n";
        }
    }
} 