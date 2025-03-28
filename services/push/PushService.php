<?php

namespace app\services\push;

use app\models\PushNotification;
use app\services\push\FirebaseService;
use app\components\ApiResponse;
use Google\Auth\Credentials\ServiceAccountCredentials;
use app\models\User;

use Yii;

class PushService
{
    protected $apiCodes;

    public function __construct()
    {
        $this->apiCodes = \app\components\response\ResponseCodes::getStatic();
    }

    /**
     * Регистрирует токен для push-уведомлений.
     *
     * @param string $token Токен устройства.
     * @param string $deviceId Идентификатор устройства.
     * @return ApiResponse Ответ API с результатом операции.
     * @throws \Exception Если пользователь не найден.
     */
    public static function registerToken($token, $deviceId, $operatingSystem)
    {
        $pushService = new PushService();
        $user = Yii::$app->user->getIdentity();
        if (!$user) throw new \Exception('User not found');
        $pushNotification = PushNotification::findOne(['push_token' => $token, 'client_id' => $user->id]);

        $existingRecord = PushNotification::findOne(['device_id' => $deviceId, 'push_token' => $token, 'client_id' => $user->id]);
        if ($existingRecord) {
            return ApiResponse::byResponseCode($pushService->apiCodes->SUCCESS, [
                'token' => $token,
                'message' => 'Token already registered for this device.',
            ]);
        }

        if ($pushNotification) {
            $pushNotification->device_id = $deviceId;
            $pushNotification->save();
            return ApiResponse::byResponseCode($pushService->apiCodes->SUCCESS, [
                'token' => $token,
            ]);
        }

        $pushNotification = new PushNotification();
        $pushNotification->push_token = $token;
        $pushNotification->client_id = $user->id;
        $pushNotification->device_id = $deviceId;
        $pushNotification->operating_system = $operatingSystem;
        if (!$pushNotification->save())
            return ApiResponse::codeErrors($pushService->apiCodes->NOT_VALIDATED, [
                'errors' => $pushNotification->errors,
            ]);

        return ApiResponse::byResponseCode($pushService->apiCodes->SUCCESS, [
            'token' => $token,
        ]);
    }

    /**
     * Удаляет токен для push-уведомлений.
     *
     * @param string $token Токен устройства.
     * @return ApiResponse Ответ API с результатом операции.
     */
    public static function deleteToken($token)
    {
        $pushService = new PushService();
        $user = Yii::$app->user->getIdentity();
        if (!$user) return ApiResponse::codeErrors($pushService->apiCodes->NOT_FOUND, ['Пользователь не найден']);
        $pushNotification = PushNotification::findOne(['push_token' => $token, 'client_id' => $user->id]);
        if (!$pushNotification) return ApiResponse::codeErrors($pushService->apiCodes->NOT_FOUND, ['Токен не найден']);
        $pushNotification->delete();
        return ApiResponse::byResponseCode($pushService->apiCodes->SUCCESS, ['Токен удален']);
    }

    /**
     * Удаляет все токены для указанного клиента.
     *
     * @return ApiResponse Ответ API с результатом операции.
     */
    public static function dropTokens()
    {
        $pushService = new PushService();
        $user = Yii::$app->user->getIdentity();
        if (!$user) return ApiResponse::codeErrors($pushService->apiCodes->NOT_FOUND, ['Пользователь не найден']);
        PushNotification::deleteAll(['client_id' => $user->id]);
        return ApiResponse::byResponseCode($pushService->apiCodes->SUCCESS, ['Токены удалены']);
    }


    /**
     * Отправляет уведомление через Firebase.
     *
     * @param string $token Токен устройства.
     * @param string $message Сообщение для отправки.
     */
    public static function sendFirebaseNotification($user_id, $message)
    {
        // Логика для отправки уведомления через Firebase
        // Например, использовать FirebaseService для отправки уведомления
    }

    /**
     * Отправляет push-уведомление клиенту.
     *
     * @param int $user_id Идентификатор пользователя.
     * @param array $message Массив с заголовком и текстом сообщения.
     * @return mixed Результат отправки уведомления.
     */
    public static function sendPushNotification($user_id, $message, bool $async = true)
    {
        if ($async) {
            return self::sendAsync($user_id, $message);
        }
        return self::sendSync($user_id, $message);
    }

    private static function sendSync($user_id, $message)
    {

        try {
            $user = User::findOne($user_id);
            echo "\n" . "\033[38;5;214m" . "[PT Count]  {$user_id}: " . count($user->pushTokens) . "\n" . "\033[0m";
            foreach ($user->pushTokens as $pushToken) {
                if ($pushToken->operating_system === 'ios') {
                    $pushToken->badge_count++;
                    $pushToken->save();
                }
                FirebaseService::sendPushNotification(
                    $user_id,
                    $message,
                    $pushToken->push_token,
                    $pushToken->operating_system
                );
            }
        } catch (\Exception $e) {
            Yii::error("Push notification error: " . $e->getMessage(), 'push');
            return $e->getMessage();
        }
    }

    private static function sendAsync($user_id, $message)
    {
        \Yii::$app->queue->push(new \app\jobs\PushNotificationJob([
            'user_id' => $user_id,
            'message' => $message
        ]));
    }

    public static function getToken()
    {

        $credentialsPath = __DIR__ . '/../../joycity.json';
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = new ServiceAccountCredentials($scopes, $credentialsPath);
        $accessToken = $credentials->fetchAuthToken()['access_token'];
        return $accessToken;
    }

    public static function resetBadge($token)
    {
        $pushService = new PushService();
        $pushToken = PushNotification::findOne(['push_token' => $token]);
        if (!$pushToken) return ApiResponse::codeErrors($pushService->apiCodes->NOT_FOUND, ['Токен не найден']);
        $pushToken->badge_count = 0;
        $pushToken->save();
        return ApiResponse::byResponseCode($pushService->apiCodes->SUCCESS, ['Бейдж сброшен']);
    }
}
