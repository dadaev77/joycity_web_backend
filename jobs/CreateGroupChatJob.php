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
                echo "\n" . "\033[32m" . 'Chat created ' . $chat->id . ' for order ' . $this->order_id . "\033[0m";
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
}
