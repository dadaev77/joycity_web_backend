<?php

$params = require __DIR__ . '/params.php';

var_dump($_ENV);
exit;

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'timeZone' => 'Europe/Moscow',
    'bootstrap' => ['log', 'queue'],
    'controllerNamespace' => 'app\console\controllers',
    'language' => 'ru-RU',
    'components' => [
        'queue' => [
            'class' => \yii\queue\db\Queue::class,
            'db' => 'db',
            'tableName' => '{{%queue}}',
            'channel' => 'default',
            'mutex' => \yii\mutex\MysqlMutex::class,
            'as log' => \yii\queue\LogBehavior::class,
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => []
        ],
        'db' => require __DIR__ . '/db.php',
    ],
    'params' => $params,
];

return $config;
