<?php

use app\components\ApiResponse;
use yii\rest\UrlRule;
use yii\web\JsonParser;
use yii\web\MultipartFormDataParser;

$params = require __DIR__ . '/params.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'timeZone' => 'Europe/Moscow',
    'bootstrap' => ['debug'],
    'modules' => [
        'debug' => [
            'class' => 'yii\debug\Module',
            'allowedIPs' => ['*'],
        ]
    ],
    'defaultRoute' => 'api',
    'language' => 'ru-RU',
    'aliases' => [
        '@bower' => '@vendor/yidas/yii2-bower-asset/bower',
    ],
    'components' => [
        'telegramLog' => [
            'class' => 'app\components\TelegramLog',
        ],
        'heartbeat' => [
            'class' => 'app\components\HeartbeatService',
        ],
        'i18n' => [
            'translations' => [
                'app*' => [
                    'basePath' => '@app/lang',
                    'class' => 'yii\i18n\PhpMessageSource',
                ],
            ],
        ],
        'profiling' => [
            'class' => 'yii\debug\Profiler',
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'on beforeSend' => static function ($event) {
                ApiResponse::handleErrors($event->sender);
            },
            'charset' => 'UTF-8',
        ],

        'request' => [
            'cookieValidationKey' => 'QgCrRBT_xzOl_VJ8-on6gIEZwkgjOofu',
            'parsers' => [
                'application/json' => JsonParser::class,
                'multipart/form-data' => MultipartFormDataParser::class,
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
        ],
        'errorHandler' => [
            'errorAction' => null,
        ],
        'mailer' => require __DIR__ . '/smtp.php',
        'log' => [
            'traceLevel' => 3,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'app\components\logs\CustomFileTarget',
                    'levels' => ['profile', 'info', 'trace'],
                    'logFile' => '@app/runtime/logs/profiling.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 5,
                ],
            ],
        ],
        'db' => require __DIR__ . '/db.php',
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'showScriptName' => true,
            'rules' => [
                [
                    'class' => UrlRule::class,
                    // Контроллеры REST API
                    'controller' => [
                        'api/v1/chat',
                        'api/v1/buyer/buyer',
                        'api/v1/buyer/feedback/buyer',
                        'api/v1/buyer/feedback/product',
                        'api/v1/buyer/order',
                        'api/v1/buyer/order/buyer-offer',
                        'api/v1/buyer/order/distribution',
                        'api/v1/buyer/product',
                        'api/v1/buyer/report',
                        'api/v1/buyer/search',
                        'api/v1/buyer/settings',
                        'api/v1/client/buyer',
                        'api/v1/client/feedback/buyer',
                        'api/v1/client/feedback/product',
                        'api/v1/client/order',
                        'api/v1/client/order/buyer-offer',
                        'api/v1/client/order/fulfillment-offer',
                        'api/v1/client/product',
                        'api/v1/client/profile',
                        'api/v1/client/search',
                        'api/v1/client/settings',
                        'api/v1/client/verification',
                        'api/v1/fulfillment/order',
                        'api/v1/fulfillment/order/fulfillment-offer',
                        'api/v1/fulfillment/order/marketplace-transaction',
                        'api/v1/fulfillment/profile',
                        'api/v1/fulfillment/report',
                        'api/v1/fulfillment/settings',
                        'api/v1/internal/constants/category',
                        'api/v1/internal/constants/delivery-point',
                        'api/v1/internal/constants/delivery-point',
                        'api/v1/internal/constants/delivery-point-address',
                        'api/v1/internal/constants/rate',
                        'api/v1/internal/constants/subcategory',
                        'api/v1/internal/constants/type-delivery',
                        'api/v1/internal/constants/type-delivery-link-category',
                        'api/v1/internal/constants/type-delivery-link-subcategory',
                        'api/v1/internal/constants/type-delivery-price',
                        'api/v1/internal/constants/type-packaging',
                        'api/v1/internal/options',
                        'api/v1/internal/order',
                        'api/v1/internal/order',
                        'api/v1/internal/profile',
                        'api/v1/internal/user',
                        'api/v1/internal/verification',
                        'api/v1/manager/order',
                        'api/v1/manager/order/buyer-offer',
                        'api/v1/manager/order/buyer-delivery-offer',
                        'api/v1/manager/order/fulfillment-offer',
                        'api/v1/manager/order/marketplace-transaction',
                        'api/v1/manager/verification',
                        'api/v1/manager/buyer',
                        'api/v1/service/profile',
                        'api/v1/notifications',
                        'api/v1/order-request',
                        'api/v1/profile',
                        'api/v1/translate',
                        'api/v1/manager/waybill',
                        'api/v1/client/waybill',
                    ],
                    'pluralize' => false,
                ],
                'swagger' => '/swagger',
                'sign-up' => 'api/v1/auth/register',

                // Правило для update-order
                'api/v1/manager/buyer/update-order/<orderId>' => 'api/v1/manager/buyer/update-order',

                'api/v1/<controller>/<id:\d+>/<action>' =>
                'api/v1/<controller>/<action>',
                'api/v1/<group>/<controller>/<action>' =>
                'api/v1/<group>/<controller>/<action>',
                'api/v1/<group>/<id:\d+>/<controller>/<action>' =>
                'api/v1/<group>/<controller>/<action>',
                'api/v1/<group>/<controller>/<id:\d+>/<action>' =>
                'api/v1/<group>/<controller>/<action>',
                'api/v1/<group>/<id:\d+>/<controller>/<subId:\d+>/<action>' =>
                'api/v1/<group>/<controller>/<action>',

                'api/v1/<module>/<controller>/<id:\d+>/<action>' =>
                'api/v1/<module>/<controller>/<action>',
                'api/v1/<module>/<group>/<id:\d+>/<controller>/<action>' =>
                'api/v1/<module>/<group>/<controller>/<action>',
                'api/v1/<module>/<group>/<controller>/<id:\d+>/<action>' =>
                'api/v1/<module>/<group>/<controller>/<action>',
                'api/v1/<module>/<group>/<id:\d+>/<controller>/<subId:\d+>/<action>' =>
                'api/v1/<module>/<group>/<controller>/<action>',
            ],
        ],
        'assetManager' => ['baseUrl' => 'api/assets'],
    ],
    'params' => $params,
];

return $config;
