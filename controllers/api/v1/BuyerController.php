<?php

namespace app\controllers\api\v1;

use app\controllers\api\V1Controller;
use app\models\User;
use yii\filters\AccessControl;

class BuyerController extends V1Controller
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
                        return User::getIdentity()->is([
                            User::ROLE_BUYER,
                            User::ROLE_BUYER_DEMO
                        ]);
                    },
                ],
            ],
        ];

        return $behaviors;
    }
}
