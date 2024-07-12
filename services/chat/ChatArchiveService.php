<?php

namespace app\services\chat;

use app\components\responseFunction\Result;
use app\components\responseFunction\ResultAnswer;
use app\models\Chat;

class ChatArchiveService
{
    public static function archiveChat(int $chatId): ResultAnswer
    {
        $chat = Chat::findOne(['id' => $chatId]);

        if (!$chat) {
            return Result::success();
        }

        $chat->is_archive = 1;

        // todo #twilio_close_chat

        if (!$chat->save()) {
            return Result::errors($chat->getFirstErrors());
        }

        return Result::success();
    }
}
