<?php

namespace app\controllers;

use app\components\ApiResponse;
use app\models\Chat;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Order;
use yii\filters\VerbFilter;
use app\models\User;
use app\models\Product;
use app\models\Order as OrderModel;

use app\services\chat\ChatConstructorService;

// rates service
use app\services\ExchangeRateService;
// modificators
use app\services\modificators\RateService;

// log service
use app\services\UserActionLogService as LogService;
use Twilio\Rest\Client;
// image processing
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
// Twilio 
use app\services\twilio\TwilioService as Twilio;
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
        'TWILIO_ACCOUNT_SID',
        'TWILIO_AUTH_TOKEN',
        'TWILIO_CONVERSATION_SERVICE_SID',
        'TWILIO_API_KEY_SID',
        'TWILIO_API_KEY_SECRET',
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

        // Reverse the order of action logs
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
        $attachments = array_diff(scandir(Yii::getAlias('@webroot/attachments')), ['.', '..', '.DS_Store', '.gitignore']);

        $keysToRemove = array_keys(array_intersect_key($_SERVER, array_flip(self::KEYS)));

        foreach ($keysToRemove as $key) {
            $logs = preg_replace('/.*' . preg_quote($key, '/') . '.*\n?/', '', $logs);
        }

        // Ограничиваем количество строк в логах
        $logs = implode("\n", array_slice(explode("\n", $logs), -500));
        $frontLogs = implode("\n", array_slice(explode("\n", $frontLogs), -500));
        $actionLogs = implode("\n", array_slice(explode("\n", $actionLogs), -100));
        $profilingLogs = implode("\n", array_slice(explode("\n", $profilingLogs), -500));

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

    /**
     * @OA\Get(
     *     path="/raw/fetch-chats",
     *     summary="Получить чаты из Twilio",
     *     @OA\Response(response="200", description="Чаты успешно получены"),
     *     @OA\Response(response="404", description="Чат не найден")
     * )
     */
    public function actionDropChats()
    {
        $twilio = \app\services\twilio\TwilioService::getClient();
        $conversations = $twilio->conversations->v1->conversations->read();

        foreach ($conversations as $conversation) {
            $conversation->delete();
        }

        return 'ok';
    }

    /**
     * @OA\Get(
     *     path="/raw/conversations",
     *     summary="Получить разговоры Twilio",
     *     @OA\Response(response="200", description="Разговоры успешно получены"),
     *     @OA\Response(response="500", description="Ошибка получения разговоров")
     * )
     */
    public function actionConversations()
    {
        try {
            $twilio = \app\services\twilio\TwilioService::getClient();
            $conversations = $twilio->conversations->v1->conversations->read();
            echo '<pre>';
            print_r($conversations);
            echo '</pre>';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @OA\Get(
     *     path="/raw/truncate-tables",
     *     summary="Очистить таблицы",
     *     @OA\Response(response="200", description="Таблицы успешно очищены"),
     *     @OA\Response(response="500", description="Ошибка очистки таблиц")
     * )
     */
    public function actionTruncateTables()
    {
        //
        $tables = [
            // 'app_option',
            'attachment',
            'buyer_delivery_offer',
            'buyer_offer',
            // 'category',
            'chat',
            'chat_translate',
            'chat_user',
            // 'delivery_point_address',
            'feedback_buyer',
            'feedback_buyer_link_attachment',
            'feedback_product',
            'feedback_product_link_attachment',
            'feedback_user',
            'feedback_user_link_attachment',
            'fulfillment_inspection_report',
            'fulfillment_marketplace_transaction',
            'fulfillment_offer',
            'fulfillment_packaging_labeling',
            'fulfillment_stock_report',
            'fulfillment_stock_report_link_attachment',
            // 'migration',
            'notification',
            'order',
            'order_distribution',
            'order_link_attachment',
            'order_rate',
            'order_tracking',
            'packaging_report_link_attachment',
            // 'privacy_policy',
            'product',
            'product_inspection_report',
            'product_link_attachment',
            'product_stock_report',
            'product_stock_report_link_attachment',
            // 'rate',
            // 'type_delivery',
            // 'type_delivery_link_category',
            // 'type_delivery_point',
            // 'type_delivery_price',
            // 'type_packaging',
            // 'user',
            // 'user_link_category',
            // 'user_link_type_delivery',
            // 'user_link_type_packaging',
            // 'user_settings',
            'user_verification_request',
        ];
        try {
            Yii::$app->db->createCommand("SET foreign_key_checks = 0")->execute();

            foreach ($tables as $table) {
                Yii::$app->db->createCommand()->truncateTable($table)->execute();
            }
            Yii::$app->db->createCommand("SET foreign_key_checks = 1")->execute();
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'status' => 'ok',
                'message' => 'Таблицы успешно очищены'
            ];
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    public function actionFormatNumber($number)
    {
        $rounded = round($number, 2);
        if (floor($rounded) == $rounded) {
            return (string) $rounded;
        } else {
            return rtrim(rtrim(number_format($rounded, 2, '.', ''), '0'), '.');
        }
    }
    public function actionTelLog()
    {
        return Yii::$app->telegramLog->send('info', 'Тестовое сообщение для тест', 'test');
    }
}
