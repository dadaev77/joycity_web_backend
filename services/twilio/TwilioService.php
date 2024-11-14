<?php

namespace app\services\twilio;

use app\components\responseFunction\Result;
use app\components\responseFunction\ResultAnswer;
use RuntimeException;
use Twilio\Exceptions\TwilioException;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\ChatGrant;
use Twilio\Rest\Client;

class TwilioService
{
    public static function addUserToConversation(
        string $personalId,
        string $chatSid
    ): ResultAnswer {
        try {
            $client = self::getClient();

            $currentParticipants = $client->conversations->v1
                ->conversations($chatSid)
                ->participants->page();

            foreach ($currentParticipants as $participant) {
                if ($participant->identity === $personalId) {
                    return Result::success($participant->sid);
                }
            }

            $newParticipant = $client->conversations->v1
                ->conversations($chatSid)
                ->participants->create([
                    'identity' => $personalId,
                ]);

            return Result::success($newParticipant->sid);
        } catch (RuntimeException | TwilioException $e) {
            return Result::error([
                'errors' => ['twilio' => $e->getMessage()]
            ]);
        }
    }

    public static function getClient(): Client
    {
        $accountSid = $_ENV['TWILIO_ACCOUNT_SID'];
        $authToken = $_ENV['TWILIO_AUTH_TOKEN'];

        if (!$accountSid || !$authToken) {
            throw new RuntimeException('Twilio credentials are empty');
        }

        return new Client($accountSid, $authToken);
    }

    public static function createConversation(): ResultAnswer
    {
        try {
            $client = self::getClient();
            $conversation = $client->conversations->v1->conversations->create();

            return Result::success($conversation);
        } catch (RuntimeException | TwilioException $e) {
            return Result::error([
                'errors' => [
                    'twilio' => $e->getMessage(),
                ]
            ]);
        }
    }

    // todo #implement
    public static function closeConversation()
    {
        /**
         * Удаление чатов
         * Поставить все сообщения в статус прочитано
         * 
         */
    }

    public static function generateJWT(
        string $identity,
        int $ttl = 86400
    ): string {
        $accountSid = $_ENV['TWILIO_ACCOUNT_SID'];
        $apiKeySid = $_ENV['TWILIO_API_KEY_SID'];
        $apiKeySecret = $_ENV['TWILIO_API_KEY_SECRET'];

        $token = new AccessToken(
            $accountSid,
            $apiKeySid,
            $apiKeySecret,
            $ttl,
            $identity
        );

        $chatGrant = new ChatGrant();
        $chatGrant->setServiceSid(self::getConversationSid());
        $token->addGrant($chatGrant);

        return $token->toJWT();
    }

    public static function getConversationSid(): string
    {
        return $_ENV['TWILIO_CONVERSATION_SERVICE_SID'];
    }
}
