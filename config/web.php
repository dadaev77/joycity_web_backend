<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$common = require __DIR__ . '/common.php';

return \yii\helpers\ArrayHelper::merge($common, [
    'id' => 'app',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'components' => [
        'db' => $db,
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'userActionLog' => [
            'class' => 'app\services\UserActionLogService',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'POST api/v1/push-services/register' => 'api/v1/push-service/register',
                'PUT api/v1/push-services/token' => 'api/v1/push-service/update-push-token',
                'GET api/v1/push-services/devices' => 'api/v1/push-service/get-devices',
            ]
        ],
    ],
    'params' => $params,
    'modules' => [
        'swagger' => [
            'class' => 'app\modules\swagger\Module',
        ],
    ],
]); 