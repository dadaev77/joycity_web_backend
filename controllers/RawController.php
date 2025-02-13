<?php

namespace app\controllers;

use app\components\ApiResponse;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Order;
use yii\filters\VerbFilter;
use app\models\User;
use app\models\Product;
use app\models\Order as OrderModel;
use app\models\Message;

// Сервисы чата
use app\services\chats\ChatService;
use app\services\chats\MessageService;

// rates service
use app\services\ExchangeRateService;
// modificators
use app\services\modificators\RateService;

// curl
use linslin\yii2\curl\Curl;
use app\services\TranslationService;
use Exception;

class RawController extends Controller
{
    public const LOG_FILE = __DIR__ . '/../runtime/logs/app.log';
    public const FRONT_LOG_FILE = __DIR__ . '/../runtime/logs/front.log';
    public const ACTION_LOG_FILE = __DIR__ . '/../runtime/logs/action.log';
    public const PROFILING_LOG_FILE = __DIR__ . '/../runtime/logs/profiling.log';
    public const SERVER_ACCESS_LOG_FILE = '/var/log/nginx/nginx-joycityrussia.store.local.access.log';
    public const SERVER_ERROR_LOG_FILE = '/var/log/nginx/nginx-joycityrussia.store.local.error.log';

    protected const KEYS = [
        'GATEWAY_INTERFACE',
        'CONTEXT_PREFIX',
        'SCRIPT_NAME',
        'PHP_SELF',
        'REQUEST_TIME_FLOAT',
        'REQUEST_TIME',
        'WEBSOCKET_CONTAINER_URL',
    ];

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = ($action->id == "acceptFrontLogs");
        return parent::beforeAction($action);
    }

    /**
     * @OA\Get(
     *     path="/raw/log",
     *     summary="Получить логи",
     *     @OA\Response(response="200", description="Логи успешно получены"),
     *     @OA\Response(response="404", description="Файлы логов не найдены")
     * )
     */
    public function actionLog()
    {
        $logs = file_exists(self::LOG_FILE) ? file_get_contents(self::LOG_FILE) : '';
        $frontLogs = file_exists(self::FRONT_LOG_FILE) ? file_get_contents(self::FRONT_LOG_FILE) : '';
        $actionLogs = file_exists(self::ACTION_LOG_FILE) ? file_get_contents(self::ACTION_LOG_FILE) : '';
        $profilingLogs = file_exists(self::PROFILING_LOG_FILE) ? file_get_contents(self::PROFILING_LOG_FILE) : '';

        if ($actionLogs) {
            $logEntries = preg_split('/<\/p>\s*/', $actionLogs, -1, PREG_SPLIT_NO_EMPTY);
            $logEntries = array_map(function ($entry) {
                return $entry . '</p>';
            }, $logEntries);
            $actionLogs = implode("\n", array_reverse($logEntries));
        }
        
            $clients = User::find()->where(['role' => 'client'])->orderBy(['id' => SORT_DESC])->all();
            $managers = User::find()->where(['role' => 'manager'])->orderBy(['id' => SORT_DESC])->all();
            $fulfillment = User::find()->where(['role' => 'fulfillment'])->orderBy(['id' => SORT_DESC])->all();
            $buyers = User::find()->where(['role' => 'buyer'])->orderBy(['id' => SORT_DESC])->all();
            $products = Product::find()->orderBy(['id' => SORT_DESC])->limit(10)->all();
            $orders = OrderModel::find()->orderBy(['id' => SORT_DESC])->limit(10)->all();
        

        $keysToRemove = array_keys(array_intersect_key($_SERVER, array_flip(self::KEYS)));

        foreach ($keysToRemove as $key) {
            
            $logs = preg_replace('/.*' . preg_quote($key, '/') . '.*\n?/', '', $logs);
        }

        // Ограничиваем количество строк в логах
        $logs = implode("\n", array_slice(explode("\n", $logs), -500));
        $frontLogs = implode("\n", array_slice(explode("\n", $frontLogs), -500));
        $actionLogs = implode("\n", array_slice(explode("\n", $actionLogs), -100));
        $profilingLogs = implode("\n", array_slice(explode("\n", $profilingLogs), -500));

        $attachments = []; // Инициализация переменной $attachments

        // Render the log view with logs and frontLogs variables
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_HTML;

        return $this->renderPartial('log', [
            'logs' => $logs,
            'frontLogs' => $frontLogs,
            'clients' => $clients,
            'managers' => $managers,
            'fulfillment' => $fulfillment,
            'buyers' => $buyers,
            'products' => $products,
            'orders' => $orders,
            'attachments' => $attachments,
            'actionLogs' => $actionLogs,
            'profilingLogs' => $profilingLogs,
        ], false);
    }

    /**
     * @OA\Post(
     *     path="/raw/clear-log",
     *     summary="Очистить лог приложения",
     *     @OA\Response(response="200", description="Лог очищен"),
     *     @OA\Response(response="500", description="Ошибка очистки лога")
     * )
     */
    public function actionClearLog()
    {
        if (file_put_contents(__DIR__ . '/../runtime/logs/app.log', '')) {
            return 'ok';
        }
        return 'error';
    }

    /**
     * @OA\Post(
     *     path="/raw/accept-front-logs",
     *     summary="Принять фронт-логи",
     *     @OA\Response(response="200", description="Логи успешно приняты"),
     *     @OA\Response(response="500", description="Не удалось добавить логи")
     * )
     */
    public function actionAcceptFrontLogs()
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $data = $request->bodyParams;
        $logs = json_encode($data, JSON_PRETTY_PRINT);

        $logs = htmlspecialchars_decode($logs);
        $logs = preg_replace('/[^\P{C}]+/u', '', $logs);
        $logs = '<pre class="format">' . $logs . '</pre>';

        if (file_exists(__DIR__ . '/../runtime/logs/front.log')) {
            $existingLogs = file_get_contents(__DIR__ . '/../runtime/logs/front.log');
            $newLogs = '[-][-][' . date('Y-m-d H:i:s') . '][-][-] ' . $logs . $existingLogs;
        } else {
            $newLogs = $logs;
        }

        // To prepend data to the file
        if (file_put_contents(__DIR__ . '/../runtime/logs/front.log', $newLogs)) {
            $response->statusCode = 200;
            $response->data = [
                'status' => 'ok',
                'message' => 'Логи успешно добавлены'
            ];
        } else {
            $response->statusCode = 500;
            $response->data = [
                'status' => 'error',
                'message' => 'Не удалось добавить логи'
            ];
        }
        return $response;
    }

    public function actionSendEmail()
    {
        $result = EmailService::sendEmail('code70@inbox.ru', 'Test', 'Test message');
        return $result;
    }
    public function actionTestLog()
    {
        Yii::error('Test message');
    }

}
