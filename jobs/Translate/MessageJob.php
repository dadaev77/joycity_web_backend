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
    public $data;

    public function execute($queue)
    {
        try {
            echo "\n" . "\033[38;5;214m" . "************************************************" . "\033[0m";
            echo "\n" . "\033[38;5;214m" . "   [TS:MESSAGE_ID] " . $this->messageId . "\033[0m";
            echo "\n" . "\033[38;5;214m" . "   [TS:MESSAGE] " . $this->message . "\033[0m";

            $message = \app\models\Message::findOne($this->messageId);
            $translations = \app\services\TranslationService::translate($this->data);
            if (is_string($translations)) {
                $translations = json_decode($translations, true);
            }

            if ($translations == null) {
                $translations = json_encode([
                    'en' => $this->message,
                    'ru' => $this->message,
                    'zh' => $this->message,
                ]);
            }
            echo "\n" . "\033[38;5;214m" . "   [TS:TRANSLATIONS] " . json_encode($translations) . "\033[0m";
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
                echo "\n" . "\033[38;5;214m" . "   [TS:MESSAGE_SAVED] TRUE\033[0m";
            } else {
                echo "\n" . "\033[38;5;214m" . "   [TS:MESSAGE_SAVED] FALSE " . $message->getErrors() . "\033[0m";
            }
            echo "\n" . "\033[38;5;214m" . "************************************************" . "\033[0m";
            return true;
        } catch (Exception $e) {
            echo "\n\033[31mОшибка перевода: " . $e->getMessage() . "\033[0m";
        }
    }
}
