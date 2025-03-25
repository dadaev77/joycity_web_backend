<?php

namespace app\services\chats;

use app\models\Message;
use yii\web\NotFoundHttpException;
use yii\db\Exception;
use yii\db\Expression;
use Yii;

class MessageService
{
    private static $supportedLanguages = ['en', 'ru', 'zh'];

    /**
     * Создать новое сообщение
     * 
     * @param bigint $chatId
     * @param bigint $userId
     * @param string $type
     * @param string|null $content
     * @param array|null $metadata
     * @param bigint|null $replyToId
     * @param array|null $attachments
     * @return Message
     */
    public static function createMessage(
        $chatId,
        $userId,
        $type,
        $content = null,
        $metadata = null,
        $replyToId = null,
        $attachments = null
    ) {
        try {
            $message = new Message([
                'chat_id' => $chatId,
                'user_id' => $userId,
                'metadata' => $metadata ? json_encode($metadata) : null,
                'reply_to_id' => $replyToId,
                'status' => 'delivered',
                'attachments' => $attachments ? json_encode($attachments) : null,
            ]);
            $message->type = $type;
            $message->content = $type === 'text' ? self::translateMessage($content, $message->id) : json_encode(['ru' => '', 'en' => '', 'zh' => '']);
            if (!$message->save()) {
                throw new Exception('Ошибка при создании сообщения: ' . json_encode($message->getErrors()));
            }
            return $message;
        } catch (\Exception $e) {
            throw new Exception('Ошибка при создании сообщения: ' . $e->getMessage());
        }
    }

    /**
     * Создать ответ на сообщение
     * 
     * @param bigint $chatId
     * @param bigint $userId
     * @param string $content
     * @param bigint $replyToMessageId
     * @param array|null $metadata
     * @return Message
     */
    public static function createReplyMessage($chatId, $userId, $content, $replyToMessageId, $metadata = null)
    {
        // Проверяем существование исходного сообщения
        $replyToMessage = Message::findOne($replyToMessageId);
        if (!$replyToMessage) {
            throw new NotFoundHttpException('Исходное сообщение не найдено');
        }

        return self::createMessage($chatId, $userId, 'text', $content, $metadata, $replyToMessageId);
    }

    /**
     * Перевести сообщение на поддерживаемые языки
     * 
     * @param string $text
     * @return array
     */
    private static function translateMessage($text, $messageId)
    {
        Yii::$app->queue->push(new \app\jobs\Translate\MessageJob([
            'message' => $text,
            'messageId' => $messageId,
        ]));

        return [
            'en' => $text,
            'ru' => $text,
            'zh' => $text,
        ];
    }
}
