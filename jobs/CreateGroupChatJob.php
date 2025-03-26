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
                echo 'Chat created';
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
}
