<?php

namespace app\services\push;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use app\models\User;
use app\components\ApiResponse;
use app\components\response\ResponseCodes;
use app\models\PushNotification;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\Auth\AuthError;
use Kreait\Firebase\Exception\Database\DatabaseError;
use Yii;

class FirebaseService
{
    protected $messaging;
    protected $apiCodes;

    public function __construct()
    {
        $this->apiCodes = ResponseCodes::getStatic();
        
        $factory = (new Factory)
            ->withServiceAccount(__DIR__ . '/joycity.json')
            ->withProjectId('joycity-stage');
            
        $this->messaging = $factory->createMessaging();
    }

    /**
     * Отправляет push-уведомление пользователю через Firebase Cloud Messaging (FCM).
     *
     * @param int $clientId Идентификатор клиента (пользователя), которому будет отправлено уведомление.
     * @param array $message Массив, содержащий заголовок и текст сообщения.
     * 
     * @return string Содержимое ответа от FCM.
     * 
     * @throws \Exception Если не найдены токены устройства для пользователя.
     */

    public static function sendPushNotification($clientId, $message, string $pushToken)
    {
        $firebaseService = new FirebaseService();
        $user = User::findOne($clientId);
        
        if (!$user) {
            return ApiResponse::byResponseCode($firebaseService->apiCodes->NOT_VALIDATED, ['message' => 'User not found']);
        }
        if (!$message) {
            return ApiResponse::byResponseCode($firebaseService->apiCodes->NOT_VALIDATED, ['message' => 'Message not found']);
        }
    
        try {
            $notification = Notification::create(
                $message['title'], 
                $message['body'],
                $_ENV['APP_URL'] . '/logo.jpg'
            );
            $message = CloudMessage::withTarget('token', $pushToken)
                ->withNotification($notification)
                ->withHighestPossiblePriority();

            $response = $firebaseService->messaging->send($message);
            
            return json_encode($response);
        } catch (AuthError $e) {
            echo 'Ошибка аутентификации: ' . $e->getMessage();
        } catch (DatabaseError $e) {
            echo 'Ошибка базы данных: ' . $e->getMessage();
        } catch (FirebaseException $e) {
            Yii::$app->actionLog->error('Ошибка Firebase: ' . $e->getMessage());
            PushNotification::findOne(['push_token' => $pushToken])->delete();
        } catch (\Throwable $e) {
            echo 'Неизвестная ошибка: ' . $e->getMessage();
        }
    }
}