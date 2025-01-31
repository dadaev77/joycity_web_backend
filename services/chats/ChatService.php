<?php

namespace app\services\chats;

use app\models\Chat;
use app\models\User;
use yii\web\NotFoundHttpException;
use yii\db\Exception;
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
            'user_id' => $firstUserId,
            'metadata' => [
                'participants' => [$firstUserId, $secondUserId]
            ]
        ]);

        if (!$chat->save()) {
            throw new \yii\db\Exception('Ошибка при создании чата: ' . json_encode($chat->getErrors()));
        }

        return $chat;
    }

    /**
     * Создать чат для верификации пользователя
     * 
     * @param bigint $userId
     * @param bigint $verificationId
     * @return Chat
     */

    // обновления в UserVerificationRequest
    public static function createVerificationChat($userId, $verificationId = null, array $metadata = [])
    {
        $chat = new Chat([
            'name' => 'Verification ' . $verificationId,
            'type' => 'private',
            'status' => 'active',
            'user_id' => $userId,
            'verification_id' => $verificationId,
            'metadata' => [
                'type' => 'verification',
            ] + $metadata

        ]);

        if (!$chat->save()) {
            throw new \yii\db\Exception('Ошибка при создании чата верификации: ' . json_encode($chat->getErrors()));
        }

        return $chat;
    }

    /**
     * Создать групповой чат
     * 
     * @param string $name
     * @param bigint $creatorId
     * @param array $participantIds
     * @param array $metadata
     * @return Chat
     */
    public static function createGroupChat(
        $name,
        $creatorId,
        array $metadata = []
    ) {
        $chat = new Chat([
            'type' => 'group',
            'name' => $name,
            'status' => 'active',
            'user_id' => $creatorId,
            'role' => 'owner',
            'metadata' => [
                'created_at' => date('Y-m-d H:i:s')
            ] + $metadata
        ]);

        if (!$chat->save()) {
            throw new \yii\db\Exception('Ошибка при создании группового чата: ' . json_encode($chat->getErrors()));
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
        if (!$chat->save()) {
            throw new \yii\db\Exception('Ошибка при изменении статуса чата: ' . json_encode($chat->getErrors()));
        }

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
        if (!$chat->save()) {
            throw new \yii\db\Exception('Ошибка при обновлении метаданных чата: ' . json_encode($chat->getErrors()));
        }

        return $chat;
    }

    /**
     * Добавить участника в чат
     * 
     * @param bigint $chatId
     * @param bigint $userId
     * @param string $role
     * @return Chat
     */
    public static function addParticipant($chatId, $userId, $role = 'member')
    {
        $chat = Chat::findOne($chatId);
        if (!$chat) {
            throw new NotFoundHttpException("Чат не найден");
        }

        $metadata = $chat->metadata ?: [];
        $participants = $metadata['participants'] ?? [];

        if (!in_array($userId, $participants)) {
            $participants[] = $userId;
            $metadata['participants'] = $participants;
            $chat->metadata = $metadata;

            if (!$chat->save()) {
                throw new \yii\db\Exception('Ошибка при добавлении участника: ' . json_encode($chat->getErrors()));
            }
        }

        return $chat;
    }

    /**
     * Удалить участника из чата
     * 
     * @param bigint $chatId
     * @param bigint $userId
     * @return Chat
     */
    public static function removeParticipant($chatId, $userId)
    {
        $chat = Chat::findOne($chatId);
        if (!$chat) {
            throw new NotFoundHttpException("Чат не найден");
        }

        $metadata = $chat->metadata ?: [];
        $participants = $metadata['participants'] ?? [];

        if (($key = array_search($userId, $participants)) !== false) {
            unset($participants[$key]);
            $metadata['participants'] = array_values($participants);
            $chat->metadata = $metadata;

            if (!$chat->save()) {
                throw new \yii\db\Exception('Ошибка при удалении участника: ' . json_encode($chat->getErrors()));
            }
        }

        return $chat;
    }
}
