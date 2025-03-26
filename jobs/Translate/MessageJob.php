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

                echo "\033[32m" . print_r([
                    'en_translate' => $translateResult['en'],
                    'ru_translate' => $translateResult['ru'],
                    'zh_translate' => $translateResult['zh'],
                ], true) . "\033[0m";

                if ($message->save()) {
                    throw new \yii\base\Exception('Остановка задания');
                }
            } else {
                echo "\033[31m" . 'message translation not updated' . "\033[0m";
            }
            return true;
        } catch (\Exception $e) {
            Yii::error("Ошибка выполнения перевода сообщения: " . $e->getMessage());
            echo "\033[31m" . 'Error: ' . $e->getMessage() . "\033[0m";
            return false;
        }
    }
}
