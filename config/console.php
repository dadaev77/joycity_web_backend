<?php

Yii::setAlias('@tests', dirname(__DIR__) . '/tests/codeception');

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$common = require __DIR__ . '/common.php';

$config = \yii\helpers\ArrayHelper::merge($common, [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'timeZone' => 'Europe/Moscow',
    'bootstrap' => ['log', 'queue'],
    'controllerNamespace' => 'app\commands',
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
        'i18n' => [
            'translations' => [
                'app*' => [
                    'basePath' => '@app/lang',
                    'class' => 'yii\i18n\PhpMessageSource',
                ],
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => '',
                'username' => '',
                'password' => '',
                'port' => '465',
                'encryption' => 'ssl',
                //'from' => array('address' => 'devnull@teftel.net', 'name' => "Nikki"),
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['scanoil'],
                    'logFile' => '@app/runtime/logs/reports/scanoil.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 20,
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['scanoil_blocks'],
                    'logFile' => '@app/runtime/logs/reports/scanoil_blocks.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 20,
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['mails'],
                    'logFile' => '@app/runtime/logs/reports/mails.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 50,
                ],
            ],
        ],
        'db' => $db,
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
]);

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
