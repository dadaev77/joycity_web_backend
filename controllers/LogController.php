<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\User;
use app\models\Product;
use app\models\Order;
use app\models\Waybill;
use app\models\BuyerOffer;
use app\models\BuyerDeliveryOffer;

/**
 * Контроллер для работы с логами
 */
class LogController extends Controller
{
    public const LOG_FILE = __DIR__ . '/../runtime/logs/app.log';
    public const FRONT_LOG_FILE = __DIR__ . '/../runtime/logs/front.log';
    public const ACTION_LOG_FILE = __DIR__ . '/../runtime/logs/action.log';
    protected const SENSITIVE_KEYS = [
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
        $this->enableCsrfValidation = ($action->id == "acceptFront");
        return parent::beforeAction($action);
    }

    /**
     * Отображение страницы логов
     */
    public function actionIndex()
    {
        // Читаем и форматируем логи
        $logs = $this->formatSystemLogs(self::LOG_FILE);
        $frontLogs = $this->formatFrontendLogs(self::FRONT_LOG_FILE);
        $actionLogs = $this->formatActionLogs(self::ACTION_LOG_FILE);

        // Получаем данные моделей
        $orders = Order::find()
            ->orderBy(['id' => SORT_DESC])
            ->limit(10)
            ->all();

        $products = Product::find()
            ->with(['category'])
            ->orderBy(['id' => SORT_DESC])
            ->limit(10)
            ->all();

        $users = User::find()
            ->orderBy(['id' => SORT_DESC])
            ->limit(10)
            ->all();

        $waybills = Waybill::find()
            ->orderBy(['id' => SORT_DESC])
            ->limit(10)
            ->all();

        $buyerOffers = BuyerOffer::find()
            ->orderBy(['id' => SORT_DESC])
            ->limit(10)
            ->all();

        $buyerDeliveryOffers = BuyerDeliveryOffer::find()
            ->orderBy(['id' => SORT_DESC])
            ->limit(10)
            ->all();

        $attachments = array_diff(scandir(Yii::getAlias('@webroot/attachments')), ['.', '..', '.DS_Store', '.gitignore']);

        return $this->renderPartial('@app/views/raw/log', [
            'logs' => $logs,
            'frontLogs' => $frontLogs,
            'actionLogs' => $actionLogs,
            'orders' => $orders,
            'products' => $products,
            'users' => $users,
            'waybills' => $waybills,
            'buyerOffers' => $buyerOffers,
            'buyerDeliveryOffers' => $buyerDeliveryOffers,
            'attachments' => $attachments,
            'tables' => $this->getAvailableTables(),
            'allowedTables' => $this->getAllowedTables(),
        ]);
    }

    /**
     * Получение списка разрешенных для очистки таблиц
     */
    private function getAllowedTables()
    {
        return [
            'app_option',
            'attachment',
            'buyer_delivery_offer',
            'buyer_offer',
            'category',
            'chat',
            'chat_translate',
            'chat_user',
            'delivery_point_address',
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
            'migration',
            'notification',
            'order',
            'order_distribution',
            'order_link_attachment',
            'order_rate',
            'order_tracking',
            'packaging_report_link_attachment',
            'privacy_policy',
            'product',
            'product_inspection_report',
            'product_link_attachment',
            'product_stock_report',
            'product_stock_report_link_attachment',
            'rate',
            'type_delivery',
            'type_delivery_link_category',
            'type_delivery_point',
            'type_delivery_price',
            'type_packaging',
            'user',
            'user_link_category',
            'user_link_type_delivery',
            'user_link_type_packaging',
            'user_settings',
            'user_verification_request',
            'waybill',
        ];
    }

    /**
     * Получение лог-файла по типу
     */
    private function getLogFile($type)
    {
        switch ($type) {
            case 'system':
                return self::LOG_FILE;
            case 'front':
                return self::FRONT_LOG_FILE;
            case 'action':
                return self::ACTION_LOG_FILE;
            default:
                return null;
        }
    }

    /**
     * Получение логов через AJAX
     */
    public function actionGet()
    {
        $type = Yii::$app->request->get('type');
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;

        $logFile = $this->getLogFile($type);
        if ($logFile === null) {
            return [
                'success' => false,
                'error' => 'Неверный тип лога'
            ];
        }

        if (!file_exists($logFile)) {
            return [
                'success' => false,
                'error' => 'Файл лога не найден'
            ];
        }

        try {
            $logContent = match ($type) {
                'system' => $this->formatSystemLogs($logFile),
                'front' => $this->formatFrontendLogs($logFile),
                'action' => $this->formatActionLogs($logFile),
                default => throw new \Exception('Неверный тип лога'),
            };

            return [
                'success' => true,
                'data' => $logContent
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Очистка лога
     */
    public function actionClear()
    {
        $type = Yii::$app->request->post('type');
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;

        $logFile = $this->getLogFile($type);
        if ($logFile === null) {
            return [
                'success' => false,
                'error' => 'Неверный тип лога'
            ];
        }

        try {
            file_put_contents($logFile, '');
            return [
                'success' => true
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Прием фронтенд логов
     */
    public function actionAcceptFront()
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $data = $request->bodyParams;

        if (empty($data)) {
            return [
                'success' => false,
                'error' => 'Нет данных для логирования'
            ];
        }

        try {
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = sprintf(
                "[-] [-] [%s] [-] [-] <pre class=\"format\">%s</pre>\n",
                $timestamp,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            file_put_contents(self::FRONT_LOG_FILE, $logEntry, FILE_APPEND);

            return [
                'success' => true
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Форматирование системных логов
     */
    private function formatSystemLogs($filePath)
    {
        if (!file_exists($filePath)) {
            return 'Файл лога не найден';
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return 'Ошибка чтения файла';
        }

        // Подсвечиваем ошибки и предупреждения
        $content = preg_replace(
            [
                '/(ERROR[^<\n]*)/i',
                '/(WARNING[^<\n]*)/i',
                '/(INFO[^<\n]*)/i',
                '/(\[[\d\-\s:]+\])/'
            ],
            [
                '<span class="text-danger">$1</span>',
                '<span class="text-warning">$1</span>',
                '<span class="text-info">$1</span>',
                '<span class="text-secondary">$1</span>'
            ],
            htmlspecialchars($content)
        );

        return nl2br($content);
    }

    /**
     * Форматирование фронтенд логов
     */
    private function formatFrontendLogs($filePath)
    {
        if (!file_exists($filePath)) {
            return 'Файл лога не найден';
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return 'Ошибка чтения файла';
        }

        $lines = explode("\n", $content);
        $formattedLogs = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            // Извлекаем временную метку и JSON
            if (preg_match('/^\[[-\s]+\]\s*\[[-\s]+\]\s*\[([\d\-\s:]+)\]\s*\[[-\s]+\]\s*\[[-\s]+\]\s*<pre class="format">(.*?)<\/pre>$/s', $line, $matches)) {
                $timestamp = $matches[1];
                $jsonStr = $matches[2];

                // Декодируем JSON
                $data = json_decode($jsonStr, true);
                if ($data === null) continue;

                // Определяем тип лога
                $logType = isset($data['error']) ? 'error' : 'info';
                $logClass = $logType === 'error' ? 'bg-danger bg-opacity-10 border-danger' : 'bg-info bg-opacity-10 border-info';

                // Форматируем основную информацию
                $header = [];
                if (isset($data['application'])) $header[] = "Application: {$data['application']}";
                if (isset($data['url'])) $header[] = "URL: {$data['url']}";

                // Форматируем request если есть
                $request = '';
                if (isset($data['request'])) {
                    $requestData = is_string($data['request']) ? json_decode($data['request'], true) : $data['request'];
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $request = '<div class="mt-2">
                            <strong>Request:</strong>
                            <pre class="mb-0 mt-1"><code class="language-json">' .
                            json_encode($requestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) .
                            '</code></pre>
                        </div>';
                    }
                }

                // Форматируем response если есть
                $response = '';
                if (isset($data['response'])) {
                    $response = '<div class="mt-2">
                        <strong>Response:</strong>
                        <pre class="mb-0 mt-1"><code class="language-json">' .
                        json_encode($data['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) .
                        '</code></pre>
                    </div>';
                }

                // Форматируем error если есть
                $error = '';
                if (isset($data['error'])) {
                    $error = '<div class="mt-2 text-danger">
                        <strong>Error:</strong> ' . htmlspecialchars($data['error']) . '
                    </div>';
                }

                // Собираем все вместе
                $formattedLogs[] = sprintf(
                    '<div class="log-entry card mb-3 border %s">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="timestamp">%s</span>
                            <span class="badge %s">%s</span>
                        </div>
                        <div class="card-body">
                            <div class="log-header small">%s</div>
                            %s
                            %s
                            %s
                        </div>
                    </div>',
                    $logClass,
                    $timestamp,
                    $logType === 'error' ? 'bg-danger' : 'bg-info',
                    strtoupper($logType),
                    implode(' | ', $header),
                    $error,
                    $request,
                    $response
                );
            }
        }

        return empty($formattedLogs) ? 'Нет логов' : implode("\n", $formattedLogs);
    }

    /**
     * Форматирование логов действий
     */
    private function formatActionLogs($filePath)
    {
        if (!file_exists($filePath)) {
            return 'Файл лога не найден';
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return 'Ошибка чтения файла';
        }

        // Подсвечиваем временные метки и действия
        $content = preg_replace(
            [
                '/(\[[\d\-\s:]+\])/',
                '/(INFO|DEBUG):/i',
                '/(ERROR|CRITICAL|ALERT|EMERGENCY):/i',
                '/(WARNING|WARN):/i'
            ],
            [
                '<span class="text-secondary">$1</span>',
                '<span class="text-info">$1:</span>',
                '<span class="text-danger">$1:</span>',
                '<span class="text-warning">$1:</span>'
            ],
            htmlspecialchars($content)
        );

        return nl2br($content);
    }

    /**
     * Удаление конфиденциальных данных
     */
    private function removeConfidentialData($content)
    {
        if (!is_string($content)) {
            return '';
        }

        $keysToRemove = array_keys(array_intersect_key($_SERVER, array_flip(self::SENSITIVE_KEYS)));
        foreach ($keysToRemove as $key) {
            if (!empty($key)) {
                $content = preg_replace('/.*' . preg_quote($key, '/') . '.*\n?/', '[REMOVED]', $content);
            }
        }

        // Дополнительно удаляем потенциально чувствительные данные
        $sensitivePatterns = [
            '/password[\'"]\s*:\s*[\'"][^\'"]+[\'"]/',
            '/token[\'"]\s*:\s*[\'"][^\'"]+[\'"]/',
            '/secret[\'"]\s*:\s*[\'"][^\'"]+[\'"]/',
            '/key[\'"]\s*:\s*[\'"][^\'"]+[\'"]/'
        ];

        foreach ($sensitivePatterns as $pattern) {
            $content = preg_replace($pattern, '$0: "[REMOVED]"', $content);
        }

        return $content;
    }

    /**
     * Получение списка доступных таблиц в БД с их схемами
     */
    private function getAvailableTables()
    {
        $tables = [];
        $schema = Yii::$app->db->schema;
        $targetTables = ['order', 'product', 'user', 'waybill', 'buyer_offer', 'buyer_delivery_offer'];

        foreach ($targetTables as $tableName) {
            if ($schema->getTableSchema($tableName)) {
                $tables[$tableName] = $schema->getTableSchema($tableName)->columns;
            }
        }

        return $tables;
    }
    /**
     * Summary of actionTruncateTable
     * @return array
     */
    public function actionTruncateTable()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'Invalid request method'];
        }

        $table = Yii::$app->request->post('table');
        if (empty($table)) {
            return ['success' => false, 'error' => 'No table specified'];
        }

        // List of allowed tables
        $allowedTables = [
            'app_option',
            'attachment',
            'buyer_delivery_offer',
            'buyer_offer',
            'category',
            'chat',
            'chat_translate',
            'chat_user',
            'delivery_point_address',
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
            'migration',
            'notification',
            'order',
            'order_distribution',
            'order_link_attachment',
            'order_rate',
            'order_tracking',
            'packaging_report_link_attachment',
            'privacy_policy',
            'product',
            'product_inspection_report',
            'product_link_attachment',
            'product_stock_report',
            'product_stock_report_link_attachment',
            'rate',
            'type_delivery',
            'type_delivery_link_category',
            'type_delivery_point',
            'type_delivery_price',
            'type_packaging',
            'user',
            'user_link_category',
            'user_link_type_delivery',
            'user_link_type_packaging',
            'user_settings',
            'user_verification_request',
            'waybill',
        ];

        if (!in_array($table, $allowedTables)) {
            return ['success' => false, 'error' => 'Invalid table name'];
        }

        try {
            Yii::$app->db->createCommand("SET foreign_key_checks = 0")->execute();
            Yii::$app->db->createCommand()->truncateTable($table)->execute();
            Yii::$app->db->createCommand("SET foreign_key_checks = 1")->execute();

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Асинхронная очистка таблиц базы данных
     */
    public function actionCleanupDatabase()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $tables = Yii::$app->request->post('tables', []);
        if (empty($tables)) {
            return ['success' => false, 'message' => 'Не выбраны таблицы для очистки'];
        }

        $availableTables = [
            'order' => Order::class,
            'product' => Product::class,
            'user' => User::class,
            'waybill' => Waybill::class,
            'buyer_offer' => BuyerOffer::class,
            'buyer_delivery_offer' => BuyerDeliveryOffer::class,
        ];

        $results = [];
        foreach ($tables as $table) {
            if (isset($availableTables[$table])) {
                try {
                    $modelClass = $availableTables[$table];
                    $count = $modelClass::deleteAll();
                    $results[$table] = [
                        'success' => true,
                        'message' => "Удалено {$count} записей",
                    ];
                } catch (\Exception $e) {
                    $results[$table] = [
                        'success' => false,
                        'message' => "Ошибка при очистке таблицы: {$e->getMessage()}",
                    ];
                }
            }
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }
}
