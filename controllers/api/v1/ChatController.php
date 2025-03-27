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
        $behaviours['verbFilter']['actions']['delete-chat'] = ['delete', 'post'];
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

    /**
     * Получение последнего сообщения в чате
     * 
     * @param Chat $chat Объект чата
     * 
     * @return Message|null Объект последнего сообщения или null, если сообщений нет
     * 
     * @throws \yii\db\StaleObjectException если объект сообщения был изменен другим пользователем
     * 
     * @example
     * ```php
     * $lastMessage = $this->getLastMessage($chat);
     * if ($lastMessage) {
     *     echo $lastMessage->content;
     * }
     * ```
     */
    private function getLastMessage($chat)
    {
        return Message::findOne($chat->last_message_id) ?? null;
    }

    /**
     * Подсчет количества непрочитанных сообщений в чате для конкретного пользователя
     * 
     * @param Chat $chat Объект чата
     * @param int $userId ID пользователя, для которого подсчитываются непрочитанные сообщения
     * 
     * @return int Количество непрочитанных сообщений
     * 
     * @throws \yii\db\StaleObjectException если объект сообщения был изменен другим пользователем
     * 
     * @example
     * ```php
     * $unreadCount = $this->calculateUnreadMessages($chat, $userId);
     * if ($unreadCount > 0) {
     *     echo "У вас есть {$unreadCount} непрочитанных сообщений";
     * }
     * ```
     * 
     * @description
     * Метод проверяет каждое сообщение в чате и подсчитывает количество сообщений,
     * которые не были прочитаны указанным пользователем. Сообщение считается
     * непрочитанным, если ID пользователя отсутствует в массиве read_by в метаданных
     * сообщения.
     */
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

    /**
     * Получение общего количества непрочитанных сообщений для текущего пользователя
     * 
     * @api {get} /api/v1/chat/get-unread-messages Получение количества непрочитанных сообщений
     * @apiName GetUnreadMessages
     * @apiGroup Chat
     * @apiVersion 1.0.0
     * 
     * @apiHeader {String} Authorization Bearer токен авторизации
     * 
     * @apiSuccess {String} status Статус операции (success)
     * @apiSuccess {Number} auth_user_id ID авторизованного пользователя
     * @apiSuccess {Number} data Общее количество непрочитанных сообщений во всех чатах пользователя
     * 
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": "success",
     *       "auth_user_id": 1,
     *       "data": 5
     *     }
     * 
     * @description
     * Метод подсчитывает общее количество непрочитанных сообщений для текущего пользователя
     * во всех его активных чатах. Для этого:
     * 1. Получает все активные чаты
     * 2. Фильтрует чаты, оставляя только те, где пользователь является участником
     * 3. Для каждого чата подсчитывает количество непрочитанных сообщений
     * 4. Возвращает сумму всех непрочитанных сообщений
     * 
     * @return array Статус операции, ID пользователя и общее количество непрочитанных сообщений
     */
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
     * Получение списка чатов текущего пользователя
     * 
     * @api {get} /api/v1/chat/get-chats Получение списка чатов
     * @apiName GetChats
     * @apiGroup Chat
     * @apiVersion 1.0.0
     * 
     * @apiHeader {String} Authorization Bearer токен авторизации
     * 
     * @apiSuccess {String} status Статус операции (success)
     * @apiSuccess {Number} auth_user_id ID авторизованного пользователя
     * @apiSuccess {Array} data Массив чатов с информацией о них
     * 
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": "success",
     *       "auth_user_id": 1,
     *       "data": [
     *         {
     *           "id": 1,
     *           "order_id": 123,
     *           "status": "active",
     *           "metadata": {
     *             "participants": [
     *               {
     *                 "id": 1,
     *                 "name": "Иван Иванов",
     *                 "avatar": "path/to/avatar.jpg",
     *                 "role": "buyer",
     *                 "email": "ivan@example.com",
     *                 "phone_number": "+79001234567",
     *                 "telegram": "@ivan",
     *                 "uuid": "550e8400-e29b-41d4-a716-446655440000",
     *                 "organization_name": "ООО Компания"
     *               }
     *             ],
     *             "last_message": {
     *               "id": 1,
     *               "content": "Текст последнего сообщения",
     *               "created_at": "2024-03-20 10:00:00"
     *             },
     *             "unread_messages": 5
     *           }
     *         }
     *       ]
     *     }
     * 
     * @description
     * Метод возвращает список всех активных чатов текущего пользователя. Для каждого чата:
     * 1. Проверяется, является ли пользователь участником чата
     * 2. Добавляется информация о последнем сообщении
     * 3. Подсчитывается количество непрочитанных сообщений
     * 4. Формируется список участников с их полными данными
     * 
     * Чаты сортируются по дате последнего обновления (новые сверху).
     * В список попадают только активные и неудаленные чаты.
     * 
     * @return array Статус операции, ID пользователя и массив чатов с информацией о них
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
     * Поиск чатов по ID заказа
     * 
     * @api {get} /api/v1/chat/search-chats Поиск чатов
     * @apiName SearchChats
     * @apiGroup Chat
     * @apiVersion 1.0.0
     * 
     * @apiHeader {String} Authorization Bearer токен авторизации
     * 
     * @apiParam {String} query Поисковый запрос (ID заказа)
     * 
     * @apiSuccess {Number} auth_user_id ID авторизованного пользователя
     * @apiSuccess {Array} chats Массив найденных чатов, сгруппированных по заказам
     * 
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "auth_user_id": 1,
     *       "chats": [
     *         {
     *           "order_id": 123,
     *           "chats": [
     *             {
     *               "id": 1,
     *               "order_id": 123,
     *               "status": "active",
     *               "metadata": {
     *                 "participants": [
     *                   {
     *                     "id": 1,
     *                     "name": "Иван Иванов",
     *                     "avatar": "path/to/avatar.jpg",
     *                     "role": "buyer",
     *                     "email": "ivan@example.com",
     *                     "phone_number": "+79001234567",
     *                     "telegram": "@ivan",
     *                     "uuid": "550e8400-e29b-41d4-a716-446655440000",
     *                     "organization_name": "ООО Компания"
     *                   }
     *                 ],
     *                 "last_message": {
     *                   "id": 1,
     *                   "content": "Текст последнего сообщения",
     *                   "created_at": "2024-03-20 10:00:00"
     *                 },
     *                 "unread_messages": 5
     *               }
     *             }
     *           ]
     *         }
     *       ]
     *     }
     * 
     * @apiErrorExample {json} Error-Response:
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "message": "Неверный формат запроса",
     *       "error": "BadRequestHttpException"
     *     }
     * 
     * @return array ID пользователя и массив найденных чатов, сгруппированных по заказам
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

    /**
     * Получение чатов для конкретного заказа
     * 
     * @api {get} /api/v1/chat/get-order-chats Получение чатов заказа
     * @apiName GetOrderChats
     * @apiGroup Chat
     * @apiVersion 1.0.0
     * 
     * @apiHeader {String} Authorization Bearer токен авторизации
     * 
     * @apiParam {Number} orderId ID заказа
     * 
     * @apiSuccess {String} status Статус операции (success)
     * @apiSuccess {Number} auth_user_id ID авторизованного пользователя
     * @apiSuccess {Array} data Массив чатов с информацией о них
     * 
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": "success",
     *       "auth_user_id": 1,
     *       "data": [
     *         {
     *           "id": 1,
     *           "order_id": 123,
     *           "status": "active",
     *           "metadata": {
     *             "participants": [
     *               {
     *                 "id": 1,
     *                 "name": "Иван Иванов",
     *                 "avatar": "path/to/avatar.jpg",
     *                 "role": "buyer",
     *                 "email": "ivan@example.com",
     *                 "phone_number": "+79001234567",
     *                 "telegram": "@ivan",
     *                 "uuid": "550e8400-e29b-41d4-a716-446655440000",
     *                 "organization_name": "ООО Компания"
     *               }
     *             ],
     *             "last_message": {
     *               "id": 1,
     *               "content": "Текст последнего сообщения",
     *               "created_at": "2024-03-20 10:00:00"
     *             },
     *             "unread_messages": 5
     *           }
     *         }
     *       ]
     *     }
     * 
     * @return array Статус операции, ID пользователя и массив чатов
     */


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
     * Отправка сообщения в чат
     * 
     * @api {post} /api/v1/chat/send-message Отправка сообщения
     * @apiName SendMessage
     * @apiGroup Chat
     * @apiVersion 1.0.0
     * 
     * @apiHeader {String} Authorization Bearer токен авторизации
     * @apiHeader {String} Content-Type multipart/form-data
     * 
     * @apiParam {Number} chat_id ID чата
     * @apiParam {String} content Текст сообщения
     * @apiParam {String} [type=text] Тип сообщения (text, image, video, file, audio)
     * @apiParam {Number} [reply_to_id] ID сообщения, на которое отвечаем
     * @apiParam {File} [images] Изображения (можно несколько)
     * @apiParam {File} [videos] Видео (можно несколько)
     * @apiParam {File} [files] Файлы (можно несколько)
     * @apiParam {File} [audios] Аудио (можно несколько)
     * 
     * @apiSuccess {String} status Статус операции (success)
     * @apiSuccess {Object} data Объект созданного сообщения
     * 
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": "success",
     *       "data": {
     *         "id": 1,
     *         "chat_id": 123,
     *         "sender_id": 1,
     *         "content": "Текст сообщения",
     *         "type": "text",
     *         "metadata": {
     *           "read_by": [1]
     *         },
     *         "created_at": "2024-03-20 10:00:00"
     *       }
     *     }
     * 
     * @apiError {String} message Сообщение об ошибке
     * @apiError {String} error Тип ошибки
     * 
     * @apiErrorExample {json} Error-Response:
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "message": "Необходимо указать chat_id",
     *       "error": "BadRequestHttpException"
     *     }
     * 
     * @return array Статус операции и данные созданного сообщения
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
            $messageToSend = Message::findOne($message->id);
            $notificationData = [
                'type' => 'new_message',
                'user_id' => $userId,
                'message' => $messageToSend->toArray(),
            ];

            \app\services\WebsocketService::sendNotification(
                array_diff($participants, [$userId]),
                $notificationData,
                true
            );

            return [
                'status' => 'success',
                'data' => $messageToSend
            ];
        } catch (\Exception $e) {
            Yii::$app->telegramLog->send('error', 'Не удалось отправить сообщение: ' . json_encode($e->getMessage()));
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Отметить сообщения как прочитанные
     * 
     * @api {put} /api/v1/chat/mark-as-read Отметить сообщения как прочитанные
     * @apiName MarkAsRead
     * @apiGroup Chat
     * @apiVersion 1.0.0
     * 
     * @apiHeader {String} Authorization Bearer токен авторизации
     * @apiHeader {String} Content-Type application/json
     * 
     * @apiParam {Number} message_id ID сообщения, для которого нужно отметить все сообщения как прочитанные
     * 
     * @apiSuccess {String} status Статус операции (success)
     * @apiSuccess {String} message Сообщение об успешном выполнении операции
     * @apiSuccess {Array} read_messages Массив прочитанных сообщений с информацией о них
     * 
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": "success",
     *       "message": "all messages in chat 123 marked as read",
     *       "read_messages": [
     *         {
     *           "id": 1,
     *           "chat_id": 123,
     *           "read_by": [1, 2],
     *           "sender_id": 2,
     *           "created_at": "2024-03-20 10:00:00"
     *         }
     *       ]
     *     }
     * 
     * @apiError {String} message Сообщение об ошибке
     * @apiError {String} error Тип ошибки
     * 
     * @apiErrorExample {json} Error-Response:
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "message": "Сообщение не найдено",
     *       "error": "BadRequestHttpException"
     *     }
     * 
     * @return array Статус операции, сообщение и список прочитанных сообщений
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
                        'sender_id' => $message->user_id,
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
                \app\services\WebsocketService::sendNotification(
                    array_diff($participants, [$userId]),
                    $notificationData,
                    true
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

    /**
     * Удаление чата
     * * Устанавливает флаг is_deleted в true и устанавливает deleted_at на текущее время.
     * @return array Статус операции.
     * @api {delete} /api/v1/chat/delete-chat Удаление чата
     * @apiName DeleteChat
     * @apiGroup Chat
     * @apiVersion 1.0.0
     * 
     * @apiHeader {String} Authorization Bearer токен авторизации
     * @apiHeader {String} Content-Type application/json
     * 
     * @apiParam {Number} chat_id ID чата для удаления
     * 
     * @apiSuccess {String} status Статус операции (success)
     * @apiSuccess {String} message Сообщение об успешном удалении
     * 
     * @apiError {String} message Сообщение об ошибке
     * @apiError {String} error Тип ошибки
     * 
     * @apiErrorExample {json} Error-Response:
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "message": "Чат не найден",
     *       "error": "BadRequestHttpException"
     *     }
     * 
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": "success",
     *       "message": "Чат успешно удален"
     *     }
     * 
     * @return array Статус операции и сообщение
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
            $notificationData = [
                'type' => 'chat_deleted',
                'chat_id' => $chatId,
                'deleter_id' => $userId,
                'deleter_name' => User::getIdentity()->name,
                'order_id' => $chat->order_id,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            try {
                \app\services\WebsocketService::sendNotification(
                    array_diff($participants, [$userId]),
                    $notificationData,
                    true
                );
            } catch (\Exception $e) {
                Yii::error("Socket notification error: " . $e->getMessage(), 'socket');
            }
        }

        return [
            'status' => 'success',
            'message' => 'Чат успешно удален'
        ];
    }

    /**
     * Удаление сообщения в чате
     * 
     * @api {delete} /api/v1/chat/delete-message Удаление сообщения
     * @apiName DeleteMessage
     * @apiGroup Chat
     * @apiVersion 1.0.0
     * 
     * @apiHeader {String} Authorization Bearer токен авторизации
     * @apiHeader {String} Content-Type application/json
     * 
     * @apiParam {Number} chat_id ID чата
     * @apiParam {Number} message_id ID сообщения для удаления
     * 
     * @apiSuccess {String} status Статус операции (success)
     * @apiSuccess {String} message Сообщение об успешном удалении
     * 
     * @apiError {String} message Сообщение об ошибке
     * @apiError {String} error Тип ошибки
     * 
     * @apiErrorExample {json} Error-Response:
     *     HTTP/1.1 400 Bad Request
     *     {
     *       "message": "Сообщение не найдено или не принадлежит чату",
     *       "error": "BadRequestHttpException"
     *     }
     * 
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": "success",
     *       "message": "Сообщение успешно удалено"
     *     }
     * 
     * @description
     * Метод выполняет "мягкое" удаление сообщения в чате:
     * 1. Проверяет существование сообщения и его принадлежность к указанному чату
     * 2. Устанавливает флаг is_deleted в true
     * 3. Записывает время удаления в поле deleted_at
     * 4. Отправляет уведомление всем участникам чата о удалении сообщения
     * 
     * При успешном удалении все участники чата получают уведомление через сокеты
     * с информацией о том, кто удалил сообщение и когда это произошло.
     * 
     * @return array Статус операции и сообщение об успешном удалении
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
            $notificationData = [
                'type' => 'message_deleted',
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'deleter_id' => $userId,
                'deleter_name' => User::getIdentity()->name,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            try {
                \app\services\WebsocketService::sendNotification(
                    array_diff($participants, [$userId]),
                    $notificationData,
                    true
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
