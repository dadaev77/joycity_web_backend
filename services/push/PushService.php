<?php

namespace app\services\push;
use app\models\PushNotification;
use app\services\push\FirebaseService;
use app\components\ApiResponse;
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
    public static function registerToken($token, $deviceId)
    {
        $pushService = new PushService();
        $user = Yii::$app->user->getIdentity();

        if (!$user) throw new \Exception('User not found');

        $pushNotification = PushNotification::findOne(['push_token' => $token, 'client_id' => $user->id]);

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
     * @param int $clientId Идентификатор клиента.
     * @return ApiResponse Ответ API с результатом операции.
     */
    public static function dropTokens($clientId)
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
     * @param int $clientId Идентификатор клиента.
     * @param string $message Сообщение для отправки.
     * @return mixed Результат отправки уведомления.
     */
    public static function sendPushNotification($user_id, $message)
    {
        return FirebaseService::sendPushNotification($user_id, $message);
    }
}