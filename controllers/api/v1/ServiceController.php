<?php

namespace app\controllers\api\v1;

use app\controllers\api\V1Controller;
use Yii;
use yii\filters\AccessControl;

class ServiceController extends V1Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'matchCallback' => function () {
                        if (empty($_ENV['SERVICE_ACCESS_TOKEN'])) {
                            return false;
                        }

                        return Yii::$app->request
                            ->getHeaders()
                            ->get('Token-Service') ===
                            $_ENV['SERVICE_ACCESS_TOKEN'];
                    },
                ],
            ],
        ];

        return $behaviors;
    }
}
