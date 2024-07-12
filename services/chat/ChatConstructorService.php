<?php

namespace app\services\chat;

use app\components\responseFunction\Result;
use app\components\responseFunction\ResultAnswer;
use app\models\Chat;
use app\models\ChatUser;
use app\models\User;
use app\services\twilio\TwilioService;
use Yii;
use yii\base\Exception;

class ChatConstructorService
{
    public static function createChatVerification(
        string $group,
        array $userIds,
        int $verificationId
    ): ResultAnswer {
        return self::createChat(
            $group,
            Chat::TYPE_VERIFICATION,
            $userIds,
            null,
            $verificationId
        );
    }

    public static function createChatOrder(
        string $group,
        array $userIds,
        int $orderId
    ): ResultAnswer {
        return self::createChat($group, Chat::TYPE_ORDER, $userIds, $orderId);
    }

    private static function createChat(
        string $group,
        string $type,
        array $userIds,
        int $orderId = null,
        int $verificationId = null,
        string $name = ''
    ): ResultAnswer {
        try {
            $usersCollection = User::find()
                ->select(['id', 'personal_id'])
                ->where(['id' => $userIds])
                ->all();
            $chat = new Chat([
                'created_at' => date('Y-m-d H:i:s'),
                'name' => $name,
                'group' => $group,
                'type' => $type,
                'order_id' => $orderId,
                'user_verification_request_id' => $verificationId,
                'twilio_id' => '',
            ]);

            $twilioConversation = TwilioService::createConversation();

            if (!$twilioConversation->success) {
                return $twilioConversation;
            }

            $chat->twilio_id = $twilioConversation->result->sid;

            if ($orderId) {
                $chat->order_id = $orderId;
            }

            if (!$chat->validate()) {
                return Result::errors($chat->getFirstErrors());
            }

            $transaction = Yii::$app->db->beginTransaction();

            if (!$chat->save()) {
                $transaction?->rollBack();

                return Result::errors($chat->getFirstErrors());
            }

            foreach ($usersCollection as $user) {
                $chatUser = new ChatUser([
                    'chat_id' => $chat->id,
                    'user_id' => $user->id,
                ]);

                if (!$chatUser->save()) {
                    $transaction?->rollBack();

                    return Result::errors($chatUser->getFirstErrors());
                }

                $twilioUserSid = TwilioService::addUserToConversation(
                    $user->personal_id,
                    $twilioConversation->result->sid
                );

                if (!$twilioUserSid->success) {
                    $transaction?->rollBack();

                    return $twilioUserSid;
                }
            }

            $chat->twilio_id = $twilioConversation->result->sid;
            $chat->save(false);

            $transaction?->commit();

            return Result::success($chat);
        } catch (Exception $e) {
            isset($transaction) && $transaction->rollBack();

            return Result::error(['errors' => ['base' => $e->getMessage()]]);
        }
    }
}
