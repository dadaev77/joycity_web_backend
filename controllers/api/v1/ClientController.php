<?php

namespace app\controllers\api\v1;

use app\controllers\api\V1Controller;
use app\models\User;
use yii\filters\AccessControl;

class ClientController extends V1Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'matchCallback' => function () {
                        $role = User::getIdentity()->role;
                        return $role === User::ROLE_CLIENT || $role === User::ROLE_CLIENT_DEMO ? true : false;
                    },
                ],
            ],
        ];

        return $behaviors;
    }
}
