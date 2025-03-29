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
            $message = \app\models\Message::findOne($this->messageId);
            echo "Message: " . $message->id . "\n";
        } catch (\Exception $e) {
        }
    }
}
