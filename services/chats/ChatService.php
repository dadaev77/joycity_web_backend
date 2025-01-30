<?php

namespace app\services;

use app\models\Chat;
use app\models\User;
use app\services\ChatParticipantService;
use yii\web\NotFoundHttpException;
use Yii;

class ChatService
{
    /**
     * Создать приватный чат между пользователями
     * 
     * @param bigint $firstUserId
     * @param bigint $secondUserId
     * @return Chat
     */
    public static function createPrivateChat($firstUserId, $secondUserId)
    {
        $chat = new Chat([
            'type' => 'private',
            'status' => 'active',
            'metadata' => json_encode([
                'participants' => [$firstUserId, $secondUserId]
            ])
        ]);

        if (!$chat->save()) {
            throw new \yii\db\Exception('Ошибка при создании чата');
        }

        // Добавляем участников
        ChatParticipantService::addParticipant($chat->id, $firstUserId, 'member');
        ChatParticipantService::addParticipant($chat->id, $secondUserId, 'member');

        return $chat;
    }

    /**
     * Создать чат для верификации пользователя
     * 
     * @param bigint $userId
     * @param bigint $verificationId
     * @return Chat
     */
    public static function createVerificationChat($userId, $verificationId)
    {
        $chat = new Chat([
            'type' => 'private',
            'status' => 'active',
            'verification_id' => $verificationId,
            'metadata' => json_encode([
                'type' => 'verification',
                'user_id' => $userId
            ])
        ]);

        if (!$chat->save()) {
            throw new \yii\db\Exception('Ошибка при создании чата верификации');
        }

        // Добавляем пользователя и администратора
        ChatParticipantService::addParticipant($chat->id, $userId, 'member');

        return $chat;
    }

    /**
     * Создать групповой чат
     * 
     * @param string $name
     * @param bigint $creatorId
     * @param array $participantIds
     * @return Chat
     */
    public static function createGroupChat($name, $creatorId, $participantIds)
    {
        $chat = new Chat([
            'type' => 'group',
            'name' => $name,
            'status' => 'active',
            'metadata' => json_encode([
                'creator_id' => $creatorId,
                'created_at' => date('Y-m-d H:i:s')
            ])
        ]);

        if (!$chat->save()) {
            throw new \yii\db\Exception('Ошибка при создании группового чата');
        }

        // Добавляем создателя как админа
        ChatParticipantService::addParticipant($chat->id, $creatorId, 'admin');

        // Добавляем остальных участников
        foreach ($participantIds as $participantId) {
            if ($participantId != $creatorId) {
                ChatParticipantService::addParticipant($chat->id, $participantId, 'member');
            }
        }

        return $chat;
    }

    /**
     * Изменить статус чата
     * 
     * @param bigint $chatId
     * @param string $newStatus
     * @return Chat
     */
    public static function changeChatStatus($chatId, $newStatus)
    {
        $chat = Chat::findOne($chatId);

        if ($chat === null) {
            throw new NotFoundHttpException("Чат не найден");
        }

        $chat->status = $newStatus;
        $chat->save();

        return $chat;
    }

    /**
     * Обновить метаданные чата
     * 
     * @param bigint $chatId
     * @param array $newMetadata
     * @return Chat
     */
    public static function updateMetadata($chatId, $newMetadata)
    {
        $chat = Chat::findOne($chatId);

        if ($chat === null) {
            throw new NotFoundHttpException("Чат не найден");
        }

        $chat->metadata = $newMetadata;
        $chat->save();

        return $chat;
    }
}
