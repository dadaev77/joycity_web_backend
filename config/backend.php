<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'timeZone' => 'Europe/Moscow',
    'language' => 'ru-RU',
    'defaultRoute' => 'web/backend/main',
    'bootstrap' => ['log'],
    'components' => [
        'i18n' => [
            'translations' => [
                'app*' => [
                    //'sourceLanguage' => 'en-US',
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/lang',
                    'fileMap' => [
                        'app' => 'app.php',
                        //'app/error' => 'error.php',
                    ],
                ],
            ],
        ],
        'authManager' => [
            'class' => 'yii\rbac\PhpManager',
            'defaultRoles' => ['guest'],
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'QgCrRBT_xzOl_VJ8-on6gIEZwkgjOofu',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'web/backend/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.mail.ru',
                'username' => 'devnull@test.net',
                'password' => 'tWew@6KEqLc6',
                'port' => '587',
                'encryption' => 'tls',
                //'from' => array('address' => 'devnull@teftel.net', 'name' => "Nikki"),
            ]
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'index' => 'web/backend/main',
                'login' => 'web/backend/login',
                'site/login' => 'web/backend/login',
                'raw/order-details' => 'raw/order-details',
                'raw/log' => 'raw/log',
                '<controller>/<action>' => 'web/backend/<controller>/<action>',
                '<controller>' => 'web/backend/<controller>/index',
                //'<action>' => 'web/backend/<action>/',
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        //'allowedIPs' => ['*']
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        //'allowedIPs' => ['*']
    ];
}

return $config;
