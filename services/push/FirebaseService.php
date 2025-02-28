<?php

namespace app\services\push;
use GuzzleHttp\Client;
use app\models\User;
use app\components\ApiResponse;
use app\components\response\ResponseCodes;

class FirebaseService
{
    protected $apiCodes;
    public function __construct()
    {
        $this->apiCodes = ResponseCodes::getStatic();
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

    public static function sendPushNotification($clientId, $message)
    {
        $firebaseService = new FirebaseService();
        $url = 'https://fcm.googleapis.com/v1/projects/joycity-stage/messages:send';
        $fcm_api_key = $_ENV['FCM_API_KEY'];

        $user = User::findOne($clientId);
        
        if (!$user) return ApiResponse::byResponseCode($firebaseService->apiCodes->NOT_VALIDATED, ['message' => 'User not found']);
        if (!$message) return ApiResponse::byResponseCode($firebaseService->apiCodes->NOT_VALIDATED, ['message' => 'Message not found']);
        
        $deviceTokens = $user->getDeviceTokens();
        
        if (empty($deviceTokens)) return ApiResponse::byResponseCode($firebaseService->apiCodes->NOT_FOUND, ['message' => 'Device tokens not found']);

        $notification = [
            'title' => $message['title'],
            'body' => $message['body'],
        ];

        $data = [
            'message' => [
                'tokens' => $deviceTokens,
                'notification' => $notification,
            ],
        ];
        try {
            $client = new Client();
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $fcm_api_key,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            return ApiResponse::byResponseCode($firebaseService->apiCodes->INTERNAL_ERROR, [
                'message' => 'Failed to send push notification',
                'error' => $e->getMessage(),
            ]);
        }
    }
}