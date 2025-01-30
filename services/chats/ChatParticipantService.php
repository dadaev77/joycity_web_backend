<?php

namespace app\services;

use app\models\ChatParticipant;
use app\models\Message;
use app\models\MessageAttachment;
use yii\web\NotFoundHttpException;
use Yii;

class ChatParticipantService
{
    /**
     * Добавить участника в чат
     * 
     * @param bigint $chatId
     * @param bigint $userId
     * @param string $role
     * @return ChatParticipant
     */
    public static function addParticipant($chatId, $userId, $role)
    {
        $participant = new ChatParticipant([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'role' => $role,
            'joined_at' => new \yii\db\Expression('NOW()'),
        ]);

        if (!$participant->save()) {
            throw new \yii\db\Exception('Ошибка при добавлении участника');
        }

        return $participant;
    }

    /**
     * Получить все сообщения пользователя
     * 
     * @param bigint $userId
     * @return Message[]
     */
    public static function getUserMessages($userId)
    {
        return Message::find()
            ->where(['user_id' => $userId])
            ->andWhere(['deleted_at' => null])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }

    /**
     * Получить все вложения пользователя
     * 
     * @param bigint $userId
     * @return MessageAttachment[]
     */
    public static function getUserAttachments($userId)
    {
        return MessageAttachment::find()
            ->alias('ma')
            ->innerJoin(['m' => Message::tableName()], 'm.id = ma.message_id')
            ->where(['m.user_id' => $userId])
            ->andWhere(['m.deleted_at' => null])
            ->orderBy(['ma.created_at' => SORT_DESC])
            ->all();
    }

    /**
     * Удалить участника из чата
     * 
     * @param bigint $chatId
     * @param bigint $userId
     * @return bool
     */
    public static function removeParticipant($chatId, $userId)
    {
        $participant = ChatParticipant::findOne(['chat_id' => $chatId, 'user_id' => $userId]);

        if ($participant === null) {
            throw new NotFoundHttpException("Участник не найден");
        }

        return $participant->delete();
    }

    /**
     * Изменить роль участника
     * 
     * @param bigint $chatId
     * @param bigint $userId
     * @param string $newRole
     * @return ChatParticipant
     */
    public static function changeRole($chatId, $userId, $newRole)
    {
        $participant = ChatParticipant::findOne(['chat_id' => $chatId, 'user_id' => $userId]);

        if ($participant === null) {
            throw new NotFoundHttpException("Участник не найден");
        }

        $participant->role = $newRole;
        $participant->save();

        return $participant;
    }
}
