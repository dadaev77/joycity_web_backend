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
     * Получить список чатов текущего пользователя
     */
    public function actionGetChats()
    {
        $userId = User::getIdentity()->id;

        $query = Chat::find()
            ->where(['user_id' => $userId])
            ->orWhere(['like', 'metadata', json_encode(['participants' => $userId])])
            ->orderBy(['updated_at' => SORT_DESC]);

        $chats = $query->all();

        foreach ($chats as $chat) {
            $metadata = $chat->metadata ?? [];
            $participants = $metadata['participants'] ?? [];
            $metadata['participants'] = [];

            $lastMessage = Message::findOne($chat->last_message_id);
            $metadata['last_message'] = $lastMessage ? $lastMessage : null;

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
            $chat->metadata = $metadata;
        }

        return [
            'status' => 'success',
            'auth_user_id' => User::getIdentity()->id,
            'data' => $chats
        ];
    }

    /**
     * Поиск по чатам
     */
    public function actionSearchChats($query = '')
    {
        $userId = User::getIdentity()->id;
        $data = [];

        if (empty($query)) {
            return [
                'chats' => []
            ];
        }

        $chatsQuery = Chat::find()
            ->where(['like', 'order_id', $query])
            ->orderBy(['id' => SORT_DESC]);

        $chats = $chatsQuery->all();

        foreach ($chats as $key => $chat) {
            $metadata = $chat->metadata ?? [];
            $participants = $metadata['participants'] ?? [];
            if (!in_array($userId, $participants)) {
                unset($chats[$key]);
            }
            $orderChats = Chat::find()->where(['order_id' => $chat->order_id])->all();

            foreach ($orderChats as $orderChat) {
                $metadata = $orderChat->metadata ?? [];
                $participants = $metadata['participants'] ?? [];
                $metadata['participants'] = [];
                if (!in_array($userId, $participants)) {
                    unset($orderChats[$key]);
                }
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
                $orderChat->metadata = $metadata;
            }

            $data[] = [
                'order_id' => $chat->order_id,
                'chats' => $orderChats,
            ];
        }

        return [
            'auth_user_id' => User::getIdentity()->id,
            'chats' => $data
        ];
    }

    /**
     * Просмотр сообщений с пагинацией
     */
    public function actionGetMessages($chatId, $page = 1, $perPage = 100)
    {
        $chat = Chat::findOne($chatId);
        if (!$chat) {
            throw new BadRequestHttpException('Чат не найден');
        }

        // Проверяем, является ли пользователь участником чата
        $userId = User::getIdentity()->id;
        $metadata = $chat->metadata ?? [];
        $participants = $metadata['participants'] ?? [];

        if ($chat->user_id !== $userId && !in_array($userId, $participants)) {
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
        $chats = Chat::find()->where(['order_id' => $orderId])->all();

        foreach ($chats as $chat) {
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
            $chat->metadata = $metadata;
        }

        return [
            'status' => 'success',
            'auth_user_id' => User::getIdentity()->id,
            'data' => $chats
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

        $chatId = Yii::$app->request->post('chat_id');
        $content = Yii::$app->request->post('content');
        $messageType = Yii::$app->request->post('type', 'text');
        $replyToId = Yii::$app->request->post('reply_to_id');

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

        Yii::$app->telegramLog->send('success', json_encode($uploadedAttachments), 'dev');

        if (!$chatId) {
            throw new BadRequestHttpException('Необходимо указать chat_id');
        }

        $chat = Chat::findOne($chatId);
        if (!$chat) {
            throw new BadRequestHttpException('Чат не найден');
        }

        $userId = User::getIdentity()->id;

        // Проверяем, является ли пользователь участником чата
        $metadata = $chat->metadata ?? [];
        $participants = $metadata['participants'] ?? [];

        if ($chat->user_id !== $userId && !in_array($userId, $participants)) {
            throw new BadRequestHttpException('У вас нет доступа к этому чату');
        }

        try {

            $message = MessageService::createMessage(
                $chatId,
                $userId,
                $messageType,
                $content,
                [],
                $replyToId,
                $uploadedAttachments,
            );

            // Обновляем last_message_id в чате
            $chat->last_message_id = $message->id;
            $chat->save();

            return [
                'status' => 'success',
                'data' => $message
            ];
        } catch (\Exception $e) {
            Yii::$app->telegramLog->send('error', $e->getMessage(), 'dev');
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}
