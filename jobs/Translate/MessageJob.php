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
        $message = \app\models\Message::findOne($this->messageId);
        echo 'message: ' . $message->id . "\n";
        $result = \app\services\TranslationService::translate($this->message);
        $translateResult = $result->result;
        echo 'translateResult: ' . json_encode($translateResult) . "\n";

        if (isset($translateResult['en']) && isset($translateResult['ru']) && isset($translateResult['zh'])) {
            $message->content = json_encode(['ru' => $translateResult['ru'], 'en' => $translateResult['en'], 'zh' => $translateResult['zh']]);
            $message->save();
            var_dump([
                'en_translate' => $translateResult['en'],
                'ru_translate' => $translateResult['ru'],
                'zh_translate' => $translateResult['zh'],
            ]);
            echo 'message translation updated' . "\n";
        }

        echo 'message translation not updated' . "\n";
    }
}
