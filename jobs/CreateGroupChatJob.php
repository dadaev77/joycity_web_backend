<?php

namespace app\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use app\services\chats\ChatService;
use Exception;
use Yii;

class CreateGroupChatJob extends BaseObject implements JobInterface
{
    public $name;
    public $creator_id;
    public $order_id;
    public $metadata;

    public function execute($queue)
    {
        try {

            $chat = ChatService::CreateGroupChat(
                $this->name,
                $this->creator_id,
                $this->order_id,
                $this->metadata
            );

            if ($chat) {
                echo "\n" . "\033[32m" . 'Создан чат ' . $chat->id . ' для заказа ' . $this->order_id . "\033[0m";
            }
            return;
        } catch (Exception $e) {
            echo "\033[31m" . 'Ошибка при создании чата: ' . $e->getMessage() . "\033[0m";
        }
    }
}
