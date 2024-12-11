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
        // Читаем и форматируем логи
        $logs = $this->formatLogs(self::LOG_FILE);
        $frontLogs = $this->formatLogs(self::FRONT_LOG_FILE);
        $actionLogs = $this->formatLogs(self::ACTION_LOG_FILE);
        
        // Получаем модели
        $clients = User::find()->where(['role' => 'client'])->orderBy(['id' => SORT_DESC])->all();
        $managers = User::find()->where(['role' => 'manager'])->orderBy(['id' => SORT_DESC])->all();
        $fulfillment = User::find()->where(['role' => 'fulfillment'])->orderBy(['id' => SORT_DESC])->all();
        $buyers = User::find()->where(['role' => 'buyer'])->orderBy(['id' => SORT_DESC])->all();
        $products = Product::find()->orderBy(['id' => SORT_DESC])->all();
        $orders = OrderModel::find()->orderBy(['id' => SORT_DESC])->all();
        
        $attachments = array_diff(scandir(Yii::getAlias('@webroot/attachments')), ['.', '..', '.DS_Store', '.gitignore']);

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_HTML;

        return $this->renderPartial('log', [
            'logs' => $logs,
            'frontLogs' => $frontLogs,
            'actionLogs' => $actionLogs,
            'clients' => $clients,
            'managers' => $managers,
            'fulfillment' => $fulfillment,
            'buyers' => $buyers,
            'products' => $products,
            'orders' => $orders,
            'attachments' => $attachments,
            'tables' => $this->getAvailableTables(),
        ], false);
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
        $content = $this->removeConfidentialData($content);
        
        // Форматируем JSON
        $content = preg_replace_callback('/({.+?})/', function($matches) {
            $json = json_decode($matches[1], true);
            if ($json === null) {
                return $matches[1];
            }
            return '<pre class="d-inline"><code class="json">' . 
                   json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . 
                   '</code></pre>';
        }, $content);

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
            $content
        );

        return nl2br($content);
    }

    /**
     * Удаление конфиденциальных данных
     */
    private function removeConfidentialData($content)
    {
        $keysToRemove = array_keys(array_intersect_key($_SERVER, array_flip(self::KEYS)));
        foreach ($keysToRemove as $key) {
            $content = preg_replace('/.*' . preg_quote($key, '/') . '.*\n?/', '[REMOVED]', $content);
        }
        return $content;
    }

    /**
     * Получение списка доступных таблиц
     */
    private function getAvailableTables()
    {
        return [
            'app_option' => 'App Options',
            'attachment' => 'Attachments',
            'buyer_delivery_offer' => 'Buyer Delivery Offers',
            'buyer_offer' => 'Buyer Offers',
            'category' => 'Categories',
            'chat' => 'Chats',
            'chat_translate' => 'Chat Translations',
            'chat_user' => 'Chat Users',
            'delivery_point_address' => 'Delivery Points',
            'feedback_buyer' => 'Buyer Feedback',
            'feedback_product' => 'Product Feedback',
            'feedback_user' => 'User Feedback',
            'fulfillment_offer' => 'Fulfillment Offers',
            'notification' => 'Notifications',
            'order' => 'Orders',
            'product' => 'Products',
            'user' => 'Users',
            // ... остальные таблицы
        ];
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

    public function actionDropChats()
    {
        $twilio = \app\services\twilio\TwilioService::getClient();
        $conversations = $twilio->conversations->v1->conversations->read();
        foreach ($conversations as $conversation) {
            $twilio->conversations->v1->conversations($conversation->sid)->delete();
        }
        return 'All chats have been deleted';
    }

    public function actionDropDB()
    {
        //
    }

    public function actionTruncateTables()
    {
        if (!Yii::$app->request->isPost) {
            return $this->redirect(['log']);
        }

        $selectedTables = Yii::$app->request->post('tables', []);
        if (empty($selectedTables)) {
            Yii::$app->session->setFlash('error', 'No tables selected');
            return $this->redirect(['log']);
        }

        try {
            Yii::$app->db->createCommand("SET foreign_key_checks = 0")->execute();
            foreach ($selectedTables as $table) {
                // Validate table name to prevent SQL injection
                if (in_array($table, [
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
                ])) {
                    Yii::$app->db->createCommand()->truncateTable($table)->execute();
                }
            }

            Yii::$app->db->createCommand("SET foreign_key_checks = 1")->execute();

            Yii::$app->session->setFlash('success', 'Selected tables have been truncated successfully');
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Error truncating tables: ' . $e->getMessage());
        }
        return $this->redirect(['log']);
    }

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

    public function actionDeleteTwilioChats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'Invalid request method'];
        }

        try {
            $twilio = \app\services\twilio\TwilioService::getClient();
            $conversations = $twilio->conversations->v1->conversations->read();
            
            // Store the total count and IDs in session for progress tracking
            $totalChats = count($conversations);
            $chatIds = array_map(function($conv) { return $conv->sid; }, $conversations);
            
            Yii::$app->session->set('twilio_deletion', [
                'total' => $totalChats,
                'remaining' => $chatIds,
                'processed' => 0,
                'errors' => [],
            ]);
            
            return ['success' => true, 'total' => $totalChats];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionTwilioDeletionProgress()
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'text/event-stream');
        Yii::$app->response->headers->add('Cache-Control', 'no-cache');
        Yii::$app->response->headers->add('Connection', 'keep-alive');

        $deletion = Yii::$app->session->get('twilio_deletion');
        if (!$deletion) {
            echo "data: " . json_encode(['completed' => true, 'message' => 'No deletion in progress']) . "\n\n";
            Yii::$app->end();
        }

        $twilio = \app\services\twilio\TwilioService::getClient();
        $remaining = $deletion['remaining'];
        $total = $deletion['total'];
        $processed = $deletion['processed'];
        $errors = $deletion['errors'];

        while (!empty($remaining)) {
            $chatId = array_pop($remaining);
            try {
                $conversation = $twilio->conversations->v1->conversations($chatId)->delete();
                $processed++;
                
                $progress = round(($processed / $total) * 100);
                echo "data: " . json_encode([
                    'progress' => $progress,
                    'message' => "Deleted chat $chatId",
                    'type' => 'success'
                ]) . "\n\n";
            } catch (\Exception $e) {
                $errors[] = "Failed to delete chat $chatId: " . $e->getMessage();
                echo "data: " . json_encode([
                    'message' => "Error deleting chat $chatId: " . $e->getMessage(),
                    'type' => 'danger'
                ]) . "\n\n";
            }

            // Update session
            Yii::$app->session->set('twilio_deletion', [
                'total' => $total,
                'remaining' => $remaining,
                'processed' => $processed,
                'errors' => $errors,
            ]);

            // Flush output buffer
            ob_flush();
            flush();
        }

        // Send completion message
        echo "data: " . json_encode([
            'completed' => true,
            'progress' => 100,
            'message' => "Completed. Processed $processed chats" . 
                        (count($errors) > 0 ? " with " . count($errors) . " errors" : ""),
            'type' => count($errors) > 0 ? 'warning' : 'success'
        ]) . "\n\n";

        Yii::$app->session->remove('twilio_deletion');
        Yii::$app->end();
    }

    /**
     * Очистка логов
     */
    public function actionClearLogs()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $type = Yii::$app->request->post('type');
        $filePath = null;
        
        switch ($type) {
            case 'system':
                $filePath = self::LOG_FILE;
                break;
            case 'front':
                $filePath = self::FRONT_LOG_FILE;
                break;
            case 'action':
                $filePath = self::ACTION_LOG_FILE;
                break;
            default:
                return ['success' => false, 'message' => 'Неверный тип логов'];
        }
        
        if ($filePath && file_exists($filePath)) {
            file_put_contents($filePath, '');
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Файл лога не найден'];
    }
    
    /**
     * Получение логов через AJAX
     */
    public function actionGetLogs()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $type = Yii::$app->request->get('type');
        $filePath = null;
        
        switch ($type) {
            case 'system-logs':
                $filePath = self::LOG_FILE;
                break;
            case 'front-logs':
                $filePath = self::FRONT_LOG_FILE;
                break;
            case 'action-logs':
                $filePath = self::ACTION_LOG_FILE;
                break;
            default:
                return ['success' => false, 'message' => 'Неверный тип логов'];
        }
        
        if ($filePath && file_exists($filePath)) {
            return [
                'success' => true,
                'logs' => $this->formatLogs($filePath)
            ];
        }
        
        return ['success' => false, 'message' => 'Файл лога не найден'];
    }
}
