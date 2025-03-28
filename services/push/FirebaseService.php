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
use Kreait\Firebase\Messaging\ApnsConfig;
use Yii;

class FirebaseService
{
    protected $messaging;
    protected $apiCodes;

    public function __construct()
    {
        $this->apiCodes = ResponseCodes::getStatic();

        $factory = (new Factory)
            ->withServiceAccount(__DIR__ . '/../../joycity.json')
            ->withProjectId($_ENV['FCM_PROJECT_ID']);

        $this->messaging = $factory->createMessaging();
    }

    /**
     * Отправляет push-уведомление пользователю через Firebase Cloud Messaging (FCM).
     *
     * @param int $clientId Идентификатор клиента (пользователя), которому будет отправлено уведомление.
     * @param array $message Массив, содержащий заголовок и текст сообщения.
     * @param string $os Операционная система устройства (android или ios).
     * 
     * @return string Содержимое ответа от FCM.
     * 
     * @throws \Exception Если не найдены токены устройства для пользователя.
     */

    public static function sendPushNotification(int $user_id, array $message, string $pushToken, string $os)
    {
        $firebaseService = new FirebaseService();
        if (!$message) {
            return ApiResponse::byResponseCode($firebaseService->apiCodes->NOT_VALIDATED, ['message' => 'Message not found']);
        }
        if ($os === 'android') {
            return $firebaseService->sendAndroidNotification($user_id, $message, $pushToken);
        } elseif ($os === 'ios') {
            return $firebaseService->sendIosNotification($user_id, $message, $pushToken);
        } else {
            return ApiResponse::byResponseCode($firebaseService->apiCodes->NOT_VALIDATED, ['message' => 'Unsupported OS']);
        }
    }

    protected function sendAndroidNotification(int $user_id, array $message, string $pushToken)
    {

        $user = User::findOne($user_id);
        $appNames = [
            'APP_NAME_CLIENT' => 'JoyCity',
            'APP_NAME_BUYER' => 'JoyCity Buyer',
            'APP_NAME_MANAGER' => 'JoyCity Manager',
            'APP_NAME_FULFILLMENT' => 'JoyCity Fulfillment',
        ];
        $message['title'] = $appNames['APP_NAME_' . strtoupper($user->role)];
        try {
            $notification = Notification::create(
                $message['title'],
                $message['body'],
            );
            $cloudMessage = CloudMessage::withTarget('token', $pushToken)
                ->withNotification($notification)
                ->withHighestPossiblePriority();

            $response = $this->messaging->send($cloudMessage);

            return json_encode($response);
        } catch (FirebaseException $e) {
            Yii::$app->actionLog->error('Ошибка Firebase: ' . $e->getMessage());
            Yii::$app->telegramLog->send('error', 'Ошибка Firebase: ' . $e->getMessage(), 'dev');
            PushNotification::findOne(['push_token' => $pushToken])->delete();
        } catch (AuthError $e) {
            Yii::$app->actionLog->error('Ошибка Firebase: ' . $e->getMessage());
            Yii::$app->telegramLog->send('error', 'Ошибка Firebase: ' . $e->getMessage(), 'dev');
            PushNotification::findOne(['push_token' => $pushToken])->delete();
        } catch (DatabaseError $e) {
            Yii::$app->actionLog->error('Ошибка Firebase: ' . $e->getMessage());
            Yii::$app->telegramLog->send('error', 'Ошибка Firebase: ' . $e->getMessage(), 'dev');
            PushNotification::findOne(['push_token' => $pushToken])->delete();
        } catch (\Throwable $e) {
            Yii::$app->telegramLog->send('error', 'Неизвестная ошибка: ' . $e->getMessage(), 'dev');
            PushNotification::findOne(['push_token' => $pushToken])->delete();
        }
        return;
    }

    protected function sendIosNotification(int $user_id, array $message, string $pushToken)
    {
        $user = User::findOne($user_id);
        $appNames = [
            'APP_NAME_CLIENT' => 'JoyCity',
            'APP_NAME_BUYER' => 'JoyCity Buyer',
            'APP_NAME_MANAGER' => 'JoyCity Manager',
            'APP_NAME_FULFILLMENT' => 'JoyCity Fulfillment',
        ];
        $message['title'] = $appNames['APP_NAME_' . strtoupper($user->role)];

        try {
            $notification = Notification::create(
                $message['title'],
                $message['body'],
            );
            $cloudMessage = CloudMessage::withTarget('token', $pushToken)
                ->withNotification($notification)
                ->withApnsConfig(ApnsConfig::fromArray([
                    'payload' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'aps' => [
                            'badge' => PushNotification::find()->where(['push_token' => $pushToken])->one()->badge_count ?? 0,
                            'alert' => [
                                'title' => $message['title'],
                                'body' => $message['body'],
                            ],
                            'sound' => 'default',
                        ],
                    ],
                ]))
                ->withHighestPossiblePriority();

            $response = $this->messaging->send($cloudMessage);

            return json_encode($response);
        } catch (FirebaseException $e) {
            Yii::$app->actionLog->error('Ошибка Firebase: ' . $e->getMessage());
            Yii::$app->telegramLog->send('error', 'Ошибка Firebase: ' . $e->getMessage(), 'dev');
            PushNotification::findOne(['push_token' => $pushToken])->delete();
        } catch (AuthError $e) {
            Yii::$app->actionLog->error('Ошибка Firebase: ' . $e->getMessage());
            Yii::$app->telegramLog->send('error', 'Ошибка Firebase: ' . $e->getMessage(), 'dev');
            PushNotification::findOne(['push_token' => $pushToken])->delete();
        } catch (DatabaseError $e) {
            Yii::$app->actionLog->error('Ошибка Firebase: ' . $e->getMessage());
            Yii::$app->telegramLog->send('error', 'Ошибка Firebase: ' . $e->getMessage(), 'dev');
            PushNotification::findOne(['push_token' => $pushToken])->delete();
        } catch (\Throwable $e) {
            echo 'Неизвестная ошибка: ' . $e->getMessage();
            Yii::$app->telegramLog->send('error', 'Неизвестная ошибка: ' . $e->getMessage(), 'dev');
            PushNotification::findOne(['push_token' => $pushToken])->delete();
        }
        return;
    }
}
