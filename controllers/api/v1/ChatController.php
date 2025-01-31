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
                        return User::getIdentity()->role === User::ROLE_BUYER ||
                            // User::getIdentity()->role === User::ROLE_BUYER_DEMO ||
                            User::getIdentity()->role === User::ROLE_CLIENT ||
                            // User::getIdentity()->role === User::ROLE_CLIENT_DEMO ||
                            User::getIdentity()->role === User::ROLE_MANAGER;
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

        return [
            'status' => 'success',
            'data' => $chats
        ];
    }

    /**
     * Поиск по чатам
     */
    public function actionSearchChats($query = '')
    {
        $userId = User::getIdentity()->id;

        $chatsQuery = Chat::find()
            ->where(['user_id' => $userId])
            ->orWhere(['like', 'metadata', json_encode(['participants' => $userId])])
            ->andWhere(['like', 'name', $query])
            ->orderBy(['updated_at' => SORT_DESC]);

        $chats = $chatsQuery->all();

        return [
            'status' => 'success',
            'data' => $chats
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
     * Отправка сообщения
     */
    public function actionSendMessage()
    {
        $chatId = Yii::$app->request->post('chat_id');
        $content = Yii::$app->request->post('content');
        $type = Yii::$app->request->post('type', 'text');
        $replyToId = Yii::$app->request->post('reply_to_id');

        if (!$chatId || !$content) {
            throw new BadRequestHttpException('Необходимо указать chat_id и content');
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
                $type,
                $content,
                null,
                $replyToId
            );

            // Обновляем last_message_id в чате
            $chat->last_message_id = $message->id;
            $chat->save();

            return [
                'status' => 'success',
                'data' => $message
            ];
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}
