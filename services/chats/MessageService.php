<?php

namespace app\services;

use app\models\Message;
use app\models\MessageAttachment;
use yii\web\NotFoundHttpException;
use Yii;
use Google\Cloud\Translate\V2\TranslateClient;

class MessageService
{
    private static $supportedLanguages = ['en', 'ru', 'es'];

    /**
     * Создать новое сообщение
     * 
     * @param bigint $chatId
     * @param bigint $userId
     * @param string $type
     * @param string|null $content
     * @param array|null $metadata
     * @param bigint|null $replyToId
     * @return Message
     */
    public static function createMessage($chatId, $userId, $type, $content = null, $metadata = null, $replyToId = null)
    {
        $message = new Message([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'type' => $type,
            'content' => $content,
            'metadata' => $metadata,
            'reply_to_id' => $replyToId,
            'status' => 'delivered',
            'created_at' => new \yii\db\Expression('NOW()'),
            'updated_at' => new \yii\db\Expression('NOW()'),
        ]);

        if (!$message->save()) {
            throw new \yii\db\Exception('Ошибка при создании сообщения');
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
        $message = self::createMessage($chatId, $userId, $type, null, $metadata, $replyToId);

        $attachment = self::uploadAttachment($message->id, $filePath, $fileName, $mimeType);

        // Обновляем сообщение с информацией о вложении
        $message->attachments = json_encode([$attachment->id]);
        $message->save();

        return $message;
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
     * @return MessageAttachment
     */
    public static function uploadAttachment($messageId, $filePath, $fileName, $mimeType, $metadata = null)
    {
        $attachment = new MessageAttachment([
            'message_id' => $messageId,
            'path' => $filePath,
            'original_name' => $fileName,
            'mime_type' => $mimeType,
            'size' => filesize($filePath),
            'metadata' => $metadata,
            'created_at' => new \yii\db\Expression('NOW()'),
        ]);

        if (!$attachment->save()) {
            throw new \yii\db\Exception('Ошибка при загрузке вложения');
        }

        return $attachment;
    }

    /**
     * Перевести сообщение на поддерживаемые языки
     * 
     * @param string $text
     * @return array
     */
    private static function translateMessage($text)
    {
        try {
            $translate = new TranslateClient([
                'key' => Yii::$app->params['googleTranslateApiKey']
            ]);

            $translations = [];
            $sourceLanguage = $translate->detectLanguage($text)['languageCode'];

            foreach (self::$supportedLanguages as $targetLanguage) {
                if ($targetLanguage !== $sourceLanguage) {
                    $result = $translate->translate($text, [
                        'source' => $sourceLanguage,
                        'target' => $targetLanguage
                    ]);
                    $translations[$targetLanguage] = $result['text'];
                }
            }

            return $translations;
        } catch (\Exception $e) {
            Yii::error('Ошибка перевода: ' . $e->getMessage());
            return [];
        }
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

        $message->save();
    }
}
