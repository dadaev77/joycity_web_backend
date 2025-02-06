<?php

namespace app\services\chats;

use app\models\Message;
use yii\web\NotFoundHttpException;
use yii\db\Exception;
use yii\db\Expression;
use Yii;
use app\services\TranslationService;

class MessageService
{
    private static $supportedLanguages = ['en', 'ru', 'cn'];

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
        $message = new Message([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'content' => self::translateMessage($content),
            'metadata' => $metadata ? json_encode($metadata) : null,
            'reply_to_id' => $replyToId,
            'status' => 'delivered',
            'attachments' => $attachments ? json_encode($attachments) : null,
        ]);
        $message->type = $type;

        if (!$message->save()) {
            throw new Exception('Ошибка при создании сообщения: ' . json_encode($message->getErrors()));
        }

        return $message;
    }

    /**
     * Создать сообщение с вложением
     * 
     * @param bigint $chatId
     * @param bigint $userId
     * @param string $type
     * @param string $filePath
     * @param string $fileName
     * @param string $mimeType
     * @param array|null $metadata
     * @param bigint|null $replyToId
     * @return Message
     */
    public static function createMessageWithAttachment($chatId, $userId, $type, $filePath, $fileName, $mimeType, $metadata = null, $replyToId = null)
    {
        // Создаем метаданные для вложения
        $attachmentMetadata = [
            'path' => $filePath,
            'original_name' => $fileName,
            'mime_type' => $mimeType,
            'size' => filesize($filePath),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Объединяем с существующими метаданными
        $fullMetadata = array_merge($metadata ?: [], [
            'attachment' => $attachmentMetadata
        ]);

        return self::createMessage($chatId, $userId, $type, null, $fullMetadata, $replyToId);
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
     * Загрузить вложение для сообщения
     * 
     * @param bigint $messageId
     * @param string $filePath
     * @param string $fileName
     * @param string $mimeType
     * @param array|null $metadata
     */
    public static function uploadAttachment($messageId, $filePath, $fileName, $mimeType, $metadata = null)
    {
        // TODO: Implement attachment upload logic
        return null;
    }

    /**
     * Перевести сообщение на поддерживаемые языки
     * 
     * @param string $text
     * @return array
     */
    private static function translateMessage($text)
    {
        $translator = new TranslationService();
        // получаем json и декодируем его в массив
        $result = json_encode($translator->translate($text), true);
        Yii::$app->telegramLog->send('success',$result);
        // возвращаем массив с ключами en, ru, cn
        return [
            'en' => $result['en'],
            'ru' => $result['ru'],
            'zh' => $result['zh'],
        ];
    }

    /**
     * Обработать событие, связанное с сообщением
     * 
     * @param bigint $messageId
     * @param string $event
     * @return void
     */
    public static function handleEvent($messageId, $event)
    {
        $message = Message::findOne($messageId);

        if ($message === null) {
            throw new NotFoundHttpException("Сообщение не найдено");
        }

        switch ($event) {
            case 'mark_read':
                $message->status = 'read';
                break;
            case 'delete':
                $message->deleted_at = new \yii\db\Expression('NOW()');
                break;
            default:
                throw new \yii\base\InvalidArgumentException("Неизвестное событие");
        }

        if (!$message->save()) {
            throw new \yii\db\Exception('Ошибка при обработке события сообщения: ' . json_encode($message->getErrors()));
        }
    }
}
