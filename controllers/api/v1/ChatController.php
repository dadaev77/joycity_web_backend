<?php

namespace app\controllers\api\v1;

use app\controllers\api\V1Controller;
use app\models\User;
use yii\filters\AccessControl;

class ChatController extends V1Controller
{
    public function behaviors()
    {
        $behaviours = parent::behaviors();
        $behaviours['verbFilter']['actions']['get-chats'] = ['get'];
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

    public function actionGetChats()
    {
        return 'get chats!';
    }

    public function actionSendMessage()
    {
        return 'send message!';
    }
}
