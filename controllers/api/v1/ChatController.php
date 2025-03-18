<?php

namespace app\controllers\api\v1;

use app\controllers\api\V1Controller;
use app\models\User;
use app\models\Chat;
use app\models\Message;
use app\services\chats\MessageService;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;
use yii\web\UploadedFile;
use app\services\ChatUploader;
use app\services\push\PushService;


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
        $behaviours['verbFilter']['actions']['delete-chat'] = ['delete'];
        $behaviours['verbFilter']['actions']['delete-message'] = ['delete'];
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
        $chats = Chat::find()->where(['status' => 'active', 'is_deleted' => false])
            ->orderBy(['updated_at' => SORT_DESC])->all();

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
                    $organizationName = null;
                    if ($user->role === User::ROLE_BUYER) {
                        $organizationName = $user->organization_name;
                    }
                    $metadata['participants'][] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar' => $user->avatar ? $user->avatar->path : null,
                        'role' => $user->role,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number,
                        'telegram' => $user->telegram,
                        'uuid' => $user->uuid,
                        'organization_name' => $organizationName,
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
                        $organizationName = null;
                        if ($user->role === User::ROLE_BUYER) {
                            $organizationName = $user->organization_name;
                        }
                        $metadata['participants'][] = [
                            'id' => $user->id,
                            'name' => $user->name,
                            'avatar' => $user->avatar ? $user->avatar->path : null,
                            'role' => $user->role,
                            'email' => $user->email,
                            'phone_number' => $user->phone_number,
                            'telegram' => $user->telegram,
                            'uuid' => $user->uuid,
                            'organization_name' => $organizationName,
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
        $chat = Chat::find()->where(['id' => $chatId, 'is_deleted' => false])->one();
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
            ->where(['chat_id' => $chatId, 'is_deleted' => false])
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
        $chats = Chat::find()->where(['order_id' => $orderId, 'status' => 'active', 'is_deleted' => false])->all();
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
                $organizationName = null;
                if ($user->role === User::ROLE_BUYER) {
                    $organizationName = $user->organization_name;
                }
                $metadata['participants'][] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar ? $user->avatar->path : null,
                    'role' => $user->role,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'telegram' => $user->telegram,
                    'uuid' => $user->uuid,
                    'organization_name' => $organizationName,
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
                } else {
                    Yii::$app->telegramLog->send('error', 'Не удалось загрузить файл в чат: ' . json_encode($result));
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

            $recievers = array_diff($participants, [$userId]);
            foreach ($recievers as $reciever) {
                $language = User::findOne($reciever)->getSettings()->application_language;
                PushService::sendPushNotification(
                    $reciever,
                    [
                        'title' => \Yii::t('chat', 'new_message', [], $language),
                        'body' => \Yii::t('chat', 'new_message', ['chat_id' => $chat->order_id], $language),
                    ]
                );
            }

            self::socketHandler(
                array_diff($participants, [$userId]),
                Message::findOne($message->id)->toArray()
            );

            return [
                'status' => 'success',
                'data' => Message::findOne($message->id)
            ];
        } catch (\Exception $e) {
            Yii::$app->telegramLog->send('error', 'Не удалось отправить сообщение: ' . json_encode($e->getMessage()));
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

        $readMessages = [];
        $messages = $chat->messages;

        foreach ($messages as $message) {
            $messageMetadata = $message->metadata ?? [];
            if (!in_array($userId, $messageMetadata['read_by'])) {
                $messageMetadata['read_by'][] = $userId;
                $message->metadata = $messageMetadata;
                if ($message->save()) {
                    $readMessages[] = [
                        'id' => $message->id,
                        'chat_id' => $message->chat_id,
                        'read_by' => $messageMetadata['read_by'],
                        'sender_id' => $message->sender_id,
                        'created_at' => $message->created_at
                    ];
                }
            }
        }

        if (!empty($readMessages)) {
            $notificationData = [
                'type' => 'messages_read',
                'chat_id' => $chat->id,
                'reader_id' => $userId,
                'reader_name' => User::getIdentity()->name,
                'messages' => $readMessages,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            try {
                self::socketHandler(
                    array_diff($participants, [$userId]),
                    $notificationData
                );
            } catch (\Exception $e) {
                Yii::error("Socket notification error: " . $e->getMessage(), 'socket');
            }
        }

        return [
            'status' => 'success',
            'message' => 'all messages in chat ' . $chat->id . ' marked as read',
            'read_messages' => $readMessages
        ];
    }

    private static function socketHandler(array $participants, $data)
    {
        $urls = ['http://joycityrussia.friflex.com:8081/notification/send'];
        $multiHandle = curl_multi_init();
        $curlHandles = [];

        foreach ($participants as $participant) {
            $ch = curl_init();
            $notificationData = json_encode([
                'notification' => [
                    'type' => 'new_message',
                    'user_id' => $participant,
                    'data' => $data,
                ],
            ]);

            Yii::debug("Sending notification: " . $notificationData, 'socket');

            curl_setopt($ch, CURLOPT_URL, $urls[0]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $notificationData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($notificationData)
            ]);

            // Добавляем отладочную информацию
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);

            curl_multi_add_handle($multiHandle, $ch);
            $curlHandles[] = $ch;
        }

        $running = null;
        do {
            $status = curl_multi_exec($multiHandle, $running);
            if ($running) {
                curl_multi_select($multiHandle);
            }
        } while ($running > 0 && $status == CURLM_OK);

        // Обработка результатов
        foreach ($curlHandles as $ch) {
            $response = curl_multi_getcontent($ch);

            // Получаем отладочную информацию
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);

            Yii::debug("Curl response: " . $response, 'socket');
            Yii::debug("Verbose info: " . $verboseLog, 'socket');

            if ($response === false) {
                Yii::error("Curl error: " . curl_error($ch), 'socket');
            }

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);
    }

    /**
     * Удаление чата
     * Устанавливает флаг is_deleted в true и устанавливает deleted_at на текущее время.
     * @return array Статус операции.
     */
    public function actionDeleteChat()
    {
        $userId = User::getIdentity()->id;
        $chatId = Yii::$app->request->post('chat_id');
        $chat = Chat::findOne($chatId);
        
        if (!$chat) {
            throw new BadRequestHttpException('Чат не найден');
        }

        $metadata = $chat->metadata ?? [];
        $participants = $metadata['participants'] ?? [];

        $chat->is_deleted = true;
        $chat->deleted_at = date('Y-m-d H:i:s');
        
        if ($chat->save()) {
            // Формируем данные для уведомления
            $notificationData = [
                'type' => 'chat_deleted',
                'chat_id' => $chatId,
                'deleter_id' => $userId,
                'deleter_name' => User::getIdentity()->name,
                'order_id' => $chat->order_id,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            try {
                // Отправляем уведомление всем участникам чата, кроме удалившего
                self::socketHandler(
                    array_diff($participants, [$userId]),
                    $notificationData
                );
            } catch (\Exception $e) {
                Yii::error("Socket notification error: " . $e->getMessage(), 'socket');
            }
        }

    /**
     * Удаление сообщения в чате
     * Устанавливает флаг is_deleted в true и устанавливает deleted_at на текущее время.
     * @return array Статус операции.
     */
    public function actionDeleteMessage()
    {
        $userId = User::getIdentity()->id;
        $chatId = Yii::$app->request->post('chat_id');
        $messageId = Yii::$app->request->post('message_id');

        $message = Message::findOne($messageId);
        if (!$message || $message->chat_id !== $chatId) {
            throw new BadRequestHttpException('Сообщение не найдено или не принадлежит чату');
        }

        $chat = Chat::findOne($chatId);
        $metadata = $chat->metadata ?? [];
        $participants = $metadata['participants'] ?? [];

        $message->is_deleted = true;
        $message->deleted_at = date('Y-m-d H:i:s');
        
        if ($message->save()) {
            // Формируем данные для уведомления
            $notificationData = [
                'type' => 'message_deleted',
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'deleter_id' => $userId,
                'deleter_name' => User::getIdentity()->name,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            try {
                // Отправляем уведомление всем участникам чата, кроме удалившего
                self::socketHandler(
                    array_diff($participants, [$userId]),
                    $notificationData
                );
            } catch (\Exception $e) {
                Yii::error("Socket notification error: " . $e->getMessage(), 'socket');
            }
        }

        return [
            'status' => 'success',
            'message' => 'Сообщение успешно удалено'
        ];
    }
}
