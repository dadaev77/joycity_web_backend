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
        $logs = $this->formatLogs(self::LOG_FILE);
        $frontLogs = $this->formatLogs(self::FRONT_LOG_FILE);
        $actionLogs = $this->formatLogs(self::ACTION_LOG_FILE);

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

        // Список таблиц для очистки
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

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_HTML;

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
            'allowedTables' => $allowedTables, // Добавляем список таблиц
        ]);
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
            $logContent = $this->formatLogs($logFile);
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
            $logs = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $logs = htmlspecialchars_decode($logs);
            $timestamp = date('Y-m-d H:i:s');

            $logEntry = "[{$timestamp}] " . $logs . PHP_EOL;
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
     * Форматирование логов
     */
    private function formatLogs($filePath)
    {
        if (!file_exists($filePath)) {
            return 'Файл лога не найден';
        }

        // Читаем файл и удаляем конфиденциальные данные
        $content = file_get_contents($filePath);
        if ($content === false) {
            return 'Ошибка чтения файла лога';
        }

        // Удаляем конфиденциальные данные
        $content = $this->removeConfidentialData($content);

        // Форматируем JSON с проверкой на валидность
        $content = preg_replace_callback('/\[[-\s]+\]\s*\[[-\s]+\]\s*\[([\d\-\s:]+)\]\s*\[[-\s]+\]\s*\[[-\s]+\]\s*(\{.+?\})/', function ($matches) {
            $timestamp = $matches[1];
            $json = json_decode($matches[2], true);
            if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
                return $matches[0]; // Если невалидный JSON, возвращаем как есть
            }
            return '<div class="log-entry">' .
                '<span class="text-secondary">[' . $timestamp . ']</span> ' .
                '<pre class="d-inline"><code class="json">' .
                htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) .
                '</code></pre></div>';
        }, $content);

        // Подсвечиваем ошибки и предупреждения с учетом возможных HTML-тегов
        $content = preg_replace(
            [
                '/(?<!>)(ERROR[^<\n]*)/i',
                '/(?<!>)(WARNING[^<\n]*)/i',
                '/(?<!>)(INFO[^<\n]*)/i',
                '/(?<!>)(\[[\d\-\s:]+\])/'
            ],
            [
                '<span class="text-danger">$1</span>',
                '<span class="text-warning">$1</span>',
                '<span class="text-info">$1</span>',
                '<span class="text-secondary">$1</span>'
            ],
            $content
        );

        // Безопасное преобразование переносов строк
        return nl2br(htmlspecialchars_decode($content, ENT_QUOTES));
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
     * Получение пути к файлу лога по типу
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
