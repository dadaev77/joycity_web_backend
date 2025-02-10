<?php

namespace app\controllers\api\v1;

use app\controllers\api\V1Controller;
use app\models\User;
use app\models\Chat;
use app\models\Message;
use app\services\chats\ChatService;
use app\services\chats\MessageService;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;
use yii\web\UploadedFile;
use app\services\ChatUploader;
use React\Http\Browser;
use React\EventLoop\Factory;


class ChatController extends V1Controller
{

    public function behaviors()
    {
        $behaviours = parent::behaviors();
        $behaviours['verbFilter']['actions']['get-chats'] = ['get'];
        $behaviours['verbFilter']['actions']['search-chats'] = ['get'];
        $behaviours['verbFilter']['actions']['get-verification-chats'] = ['get'];
        $behaviours['verbFilter']['actions']['get-messages'] = ['get'];
        $behaviours['verbFilter']['actions']['send-message'] = ['post'];
        $behaviours['verbFilter']['actions']['mark-as-read'] = ['put'];
        $behaviours['verbFilter']['actions']['get-unread-messages'] = ['get'];
        $behaviours['verbFilter']['actions']['get-order-chats'] = ['get'];
        $behaviours['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'matchCallback' => function () {
                        // User::getIdentity()->role === User::ROLE_BUYER_DEMO ||
                        // User::getIdentity()->role === User::ROLE_CLIENT_DEMO ||
                        return User::getIdentity()->role === User::ROLE_BUYER ||
                            User::getIdentity()->role === User::ROLE_CLIENT ||
                            User::getIdentity()->role === User::ROLE_MANAGER ||
                            User::getIdentity()->role === User::ROLE_FULFILLMENT;
                    },
                ],
            ],
        ];
        return $behaviours;
    }

    private function getLastMessage($chat)
    {
        return Message::findOne($chat->last_message_id) ?? null;
    }

    private function calculateUnreadMessages($chat, $userId)
    {
        $unreadMessages = 0;
        $chatMessages = $chat->messages ?? [];
        foreach ($chatMessages as $message) {
            $messageMetadata = $message->metadata ?? [];
            $readBy = $messageMetadata['read_by'] ?? [];
            if (!in_array($userId, $readBy)) {
                $unreadMessages++;
            }
        }
        return $unreadMessages;
    }

    public function actionGetUnreadMessages()
    {
        $userId = User::getIdentity()->id;
        $userChats = [];
        $chats = Chat::find()->where(['status' => 'active'])->all();
        foreach ($chats as $chat) {
            $metadata = $chat->metadata ?? [];
            $participants = $metadata['participants'] ?? [];
            if (in_array($userId, $participants)) {
                $userChats[] = $chat;
            }
        }

        $unreadMessages = 0;
        
        foreach ($userChats as $chat) {
            $unreadMessages += $this->calculateUnreadMessages($chat, $userId);
        }
        return [
            'status' => 'success',
            'auth_user_id' => User::getIdentity()->id,
            'data' => $unreadMessages
        ];
    }
    /**
     * Получить список чатов текущего пользователя
     */
    public function actionGetChats()
    {
        $userId = User::getIdentity()->id;
        $filteredChats = [];
        $chats = Chat::find()->where(['status' => 'active'])->orderBy(['updated_at' => SORT_DESC])->all();

        foreach ($chats as $chat) {
            $metadata = $chat->metadata ?? [];
            $participants = $metadata['participants'] ?? [];
            $metadata['last_message'] = $this->getLastMessage($chat);
            $metadata['unread_messages'] = $this->calculateUnreadMessages($chat, $userId);
            $chat->metadata = $metadata;
            
            if (in_array($userId, $participants)) {
                $filteredChats[] = $chat;
            }

        }
        
        foreach ($filteredChats as $chat) {
            $metadata = $chat->metadata ?? [];
            $participants = $metadata['participants'] ?? [];
            $metadata['participants'] = [];

            foreach ($participants as $participant) {
                $user = User::findOne($participant);
                if ($user) {
                $metadata['participants'][] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'telegram' => $user->telegram,
                    ];
                }
            }
            $chat->metadata = $metadata;
        }

        return [
            'status' => 'success',
            'auth_user_id' => User::getIdentity()->id,
            'data' => $filteredChats
        ];
    }

    /**
     * Поиск по чатам
     */
    public function actionSearchChats($query = '')
    {
        $userId = User::getIdentity()->id;
        $data = [];
        
        if (empty($query)) return ['chats' => []];

        $orders = \app\models\Order::find()
            ->where(['like', 'id', $query])
            ->all();

        foreach ($orders as $order) {
            $filteredChats = [];
            $chats = Chat::find()->where(['order_id' => $order->id, 'status' => 'active'])->all();
            foreach ($chats as $chat) {
                $metadata = $chat->metadata ?? [];
                $participants = $metadata['participants'] ?? [];
                $metadata['unread_messages'] = $this->calculateUnreadMessages($chat, $userId);
                $metadata['last_message'] = $this->getLastMessage($chat);
                $metadata['participants'] = [];
                foreach ($participants as $participant) {
                    $user = User::findOne($participant);
                    if ($user) {
                        $metadata['participants'][] = [
                            'id' => $user->id,
                            'name' => $user->name,
                            'avatar' => $user->avatar,
                            'role' => $user->role,
                            'email' => $user->email,
                            'phone_number' => $user->phone_number,
                            'telegram' => $user->telegram,
                        ];
                    }
                }
                $chat->metadata = $metadata;
                if (in_array($userId, $participants)) {
                    $filteredChats[] = $chat;
                }
            }
            if (!empty($filteredChats)) {
                $data[] = [
                    'order_id' => $order->id,
                    'chats' => $filteredChats,
                ];
            }
        }

        return [
            'auth_user_id' => User::getIdentity()->id,
            'chats' => $data
        ];
    }

    /**
     * Просмотр сообщений с пагинацией
     */
    public function actionGetMessages($chatId, $perPage = 100, $page = 1)
    {
        $chat = Chat::findOne($chatId);
        if (!$chat) {
            throw new BadRequestHttpException('Чат не найден');
        }

        // Проверяем, является ли пользователь участником чата
        $userId = User::getIdentity()->id;
        $metadata = $chat->metadata ?? [];
        $participants = $metadata['participants'] ?? [];

        if ($userId && !in_array($userId, $participants)) {
            throw new BadRequestHttpException('У вас нет доступа к этому чату');
        }

        $query = Message::find()
            ->where(['chat_id' => $chatId])
            ->orderBy(['created_at' => SORT_DESC]);

        $countQuery = clone $query;
        
        $pages = new Pagination([
            'totalCount' => $countQuery->count(),
            'pageSize' => $perPage,
            'page' => $page - 1
        ]);

        $messages = $query->offset($pages->offset)
            ->limit($pages->limit)
            ->all();

        return [
            'status' => 'success',
            'auth_user_id' => User::getIdentity()->id,
            'data' => [
                'messages' => $messages,
                'pagination' => [
                    'total' => $pages->totalCount,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($pages->totalCount / $perPage)
                ]
            ]
        ];
    }
    public function actionGetOrderChats($orderId)
    {
        $chats = Chat::find()->where(['order_id' => $orderId, 'status' => 'active'])->all();
        $userId = User::getIdentity()->id;
        $filteredChats = [];

        foreach ($chats as $chat) {
            $metadata = $chat->metadata ?? [];
            $participants = $metadata['participants'] ?? [];
            if (in_array($userId, $participants)) {
                $filteredChats[] = $chat;
            }
            continue;
        }


        foreach ($filteredChats as $chat) {
            $metadata = $chat->metadata ?? [];
            $participants = $metadata['participants'] ?? [];
            $metadata['participants'] = [];
            foreach ($participants as $participant) {
                $user = User::findOne($participant);
                $metadata['participants'][] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'telegram' => $user->telegram,
                ];
            }
            $metadata['last_message'] = $this->getLastMessage($chat);
            $metadata['unread_messages'] = $this->calculateUnreadMessages($chat, $userId);
            $chat->metadata = $metadata;
        }

        return [
            'status' => 'success',
            'auth_user_id' => User::getIdentity()->id,
            'data' => $filteredChats
        ];
    }
    /**
     * Отправка сообщения
     */
    public function actionSendMessage()
    {
        $uploadedTypes = [
            'images' => UploadedFile::getInstancesByName('images'),
            'videos' => UploadedFile::getInstancesByName('videos'),
            'files' => UploadedFile::getInstancesByName('files'),
            'audios' => UploadedFile::getInstancesByName('audios'),
        ];

        $uploadedAttachments = [];
        foreach ($uploadedTypes as $type => $files) {
            if ($files) {
                $methodName = 'upload' . ucfirst($type);
                $result = call_user_func([ChatUploader::class, $methodName], $files);
                if (!empty($result)) {
                    $uploadedAttachments = array_merge($uploadedAttachments, $result);
                }
            }
        }

        $chatId = Yii::$app->request->post('chat_id');
        $content = Yii::$app->request->post('content');
        $messageType = Yii::$app->request->post('type', 'text');
        $replyToId = Yii::$app->request->post('reply_to_id');

        if (!$chatId) {
            throw new BadRequestHttpException('Необходимо указать chat_id');
        }

        $chat = Chat::findOne($chatId);
        if (!$chat) {
            throw new BadRequestHttpException('Чат не найден');
        }

        $userId = User::getIdentity()->id;
        $metadata = $chat->metadata ?? [];
        $participants = $metadata['participants'] ?? [];
        
        if (!in_array($userId, $participants)) {
            throw new BadRequestHttpException('У вас нет доступа к этому чату');
        }

        try {
            $message = MessageService::createMessage(
                $chatId,
                $userId,
                $messageType,
                $content,
                ['read_by' => [$userId]],
                $replyToId,
                $uploadedAttachments,
            );

            $chat->last_message_id = $message->id;
            $chat->save();

            self::socketHandler($participants, Message::findOne($message->id) ? Message::findOne($message->id)->toArray() : null);

            return [
                'status' => 'success',
                'data' => Message::findOne($message->id)
            ];

        } catch (\Exception $e) {
            Yii::$app->telegramLog->send('error', $e->getMessage(), 'dev');
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Отметить сообщения как прочитанные
     */
    public function actionMarkAsRead()
    {
        $userId = User::getIdentity()->id;
        $messageId = Yii::$app->request->post('message_id');
        $message = Message::findOne($messageId);
        if (!$message) {
            throw new BadRequestHttpException('Сообщение не найдено');
        }

        $chat = $message->chat;
        $metadata = $chat->metadata ?? [];
        $participants = $metadata['participants'] ?? [];

        if (!in_array($userId, $participants)) {
            throw new BadRequestHttpException('У вас нет доступа к этому чату');
        }

        $messages = $chat->messages;
        foreach ($messages as $message) {            
            $messageMetadata = $message->metadata ?? [];
            if (!in_array($userId, $messageMetadata['read_by'])) {
                $messageMetadata['read_by'][] = $userId;
                $message->metadata = $messageMetadata;
                $message->save();
            }
        }

        return [
            'status' => 'success',
            'message' => 'all messages in chat ' . $chat->id . ' marked as read'
        ];

    }

    private static function socketHandler(array $participants, $message)
    {
        $urls = [$_ENV['APP_URL_NOTIFICATIONS'] . '/notification/send'];
        $multiHandle = curl_multi_init();
        $curlHandles = [];

        foreach ($participants as $participant) {
            $ch = curl_init();
            $data = json_encode([
                'notification' => [
                    'type' => 'new_message',
                    'user_id' => $participant,
                    'message' => $message,
                ],
            ]);

            curl_setopt($ch, CURLOPT_URL, $urls[0]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

            curl_multi_add_handle($multiHandle, $ch);
            $curlHandles[] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
        } while ($running > 0);

        foreach ($curlHandles as $ch) {
            $response = curl_multi_getcontent($ch);
            curl_multi_remove_handle($multiHandle, $ch);
        }

        curl_multi_close($multiHandle);
    }
}
