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
                        return 1;
                        return User::getIdentity()->is([
                            User::ROLE_CLIENT,
                            User::ROLE_CLIENT_DEMO
                        ]);
                    },
                ],
            ],
        ];

        return $behaviors;
    }
}
