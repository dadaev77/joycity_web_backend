<?php

namespace app\jobs\Translate;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use Exception;
use Yii;

class MessageJob extends BaseObject implements JobInterface
{
    public $message;
    public $messageId;
    /**
     * Братан, это джобка
     * а как и че тут дальше делать, спросишь?
     * 
     * я без понятия, я только сделал шаблон
     */


    /** Отправка события на клиент о том, что сообщение переведено
     * \app\services\WebsocketService::sendNotification($message->chat->metadata['participants'], [
     * 'type' => 'translate_message',
     * 'message' => json_decode($message->content, true),
     * 'message_id' => $message->id,
     * 'chat_id' => $message->chat_id,
     * 'multiple' => false,
     * 'async' => false,
     * ]);
     */

    public function execute($queue)
    {
        try {
            echo "\n\033[32mНачало выполнения работы: " . $this->message . "\033[0m";
            $message = \app\models\Message::findOne($this->messageId);
            $translations = \app\services\TranslationService::translate($this->data);
            if (is_string($translations)) {
                $translations = json_decode($translations, true);
            }
            $message->content = $translations;
            if ($message->save()) {
                \app\services\WebsocketService::sendNotification($message->chat->metadata['participants'], [
                    'type' => 'translate_message',
                    'message' => json_decode($message->content, true),
                    'message_id' => $message->id,
                    'chat_id' => $message->chat_id,
                    'multiple' => false,
                    'async' => false,
                ]);
            } else {
                echo "\n\033[31mОшибка сохранения сообщения: " . $message->getErrors() . "\033[0m";
            }
            return true;
        } catch (Exception $e) {
            echo "\n\033[31mОшибка перевода: " . $e->getMessage() . "\033[0m";
        }
    }
}
