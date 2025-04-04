<?php

namespace app\controllers\api\v1;

use app\controllers\api\V1Controller;
use yii\filters\AccessControl;

class InternalController extends V1Controller
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
                        return true;
                    },
                ],
            ],
        ];

        return $behaviors;
    }
}
