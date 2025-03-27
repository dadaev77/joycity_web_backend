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

    public function execute($queue)
    {
        try {
            $message = \app\models\Message::findOne($this->messageId);

            $result = \app\services\TranslationService::translate($this->message);
            $translateResult = $result->result;

            if (isset($translateResult['en']) && isset($translateResult['ru']) && isset($translateResult['zh'])) {
                $message->content = json_encode(['ru' => $translateResult['ru'], 'en' => $translateResult['en'], 'zh' => $translateResult['zh']]);
                if ($message->save()) {
                    echo "\n" . "\033[32m" . '[Translate] Сообщение ' . $message->id . ' переведено' . "\033[0m \n";
                    echo print_r([
                        'en_translate' => $translateResult['en'],
                        'ru_translate' => $translateResult['ru'],
                        'zh_translate' => $translateResult['zh'],
                    ], true);
                    echo "\033[32m" . '[Translate] Конец сообщения' . "\033[0m";

                    \app\services\WebsocketService::sendNotification($message->chat->metadata['participants'], [
                        'type' => 'translate_message',
                        'data' => [
                            'message' => json_decode($message->content, true),
                            'message_id' => $message->id,
                            'chat_id' => $message->chat_id,
                        ],
                        'multiple' => false,
                        'async' => false,
                    ]);
                } else {
                    echo "\n" . "\033[31m" . '[Translate] Не удалось обновить переводы для сообщения ' . $message->id . "\033[0m";
                }
            } else {
                echo "\n" . "\033[31m" . '[Translate] Ответ сервера не содержит переводов' . "\033[0m";
            }
            return true;
        } catch (\Exception $e) {
            Yii::error("Ошибка выполнения перевода сообщения: " . $e->getMessage());
            echo "\n" . "\033[31m" . '[Translate] Error: ' . $e->getMessage() . "\033[0m";
            return false;
        }
    }
}
