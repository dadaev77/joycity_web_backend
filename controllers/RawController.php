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
use app\services\twilio\TwilioService;
use app\services\UserActionLogService as LogService;
use Twilio\Rest\Client;
// image processing
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;


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

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = ($action->id == "acceptFrontLogs");
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $orders = Order::find()->all();
        include(__DIR__ . '/../views/raw/index.php');
    }
    public function actionLog()
    {
        $logs = file_exists(self::LOG_FILE) ? file_get_contents(self::LOG_FILE) : 'Log file not found';
        $frontLogs = file_exists(self::FRONT_LOG_FILE) ? file_get_contents(self::FRONT_LOG_FILE) : 'Front log file not found';
        $actionLogs = file_exists(self::ACTION_LOG_FILE) ? file_get_contents(self::ACTION_LOG_FILE) : 'Action log file not found';
        $serverAccessLogs = 'Server access log file not found';
        $serverErrorLogs = 'Server error log file not found';
        $clients = User::find()->where(['role' => 'client'])->orderBy(['id' => SORT_DESC])->all();
        $managers = User::find()->where(['role' => 'manager'])->orderBy(['id' => SORT_DESC])->all();
        $fulfillment = User::find()->where(['role' => 'fulfillment'])->orderBy(['id' => SORT_DESC])->all();
        $buyers = User::find()->where(['role' => 'buyer'])->orderBy(['id' => SORT_DESC])->all();
        $products = Product::find()->orderBy(['id' => SORT_DESC])->all();
        $orders = OrderModel::find()->orderBy(['id' => SORT_DESC])->all();
        $attachments = array_diff(scandir(Yii::getAlias('@webroot/attachments')), ['.', '..', '.DS_Store', '.gitignore']);

        $keysToRemove = array_keys(array_intersect_key($_SERVER, array_flip(self::KEYS)));
        $lines = explode("\n", $logs);
        foreach ($keysToRemove as $key) {
            $logs = preg_replace('/.*' . preg_quote($key, '/') . '.*\n?/', '', $logs);
        }

        // limit to 1000 lines
        $logs = implode("\n", array_slice(explode("\n", $logs), 0, 2000));
        $frontLogs = implode("\n", array_slice(explode("\n", $frontLogs), 0, 2000));

        // format logs content
        $logs = nl2br($logs);
        $frontLogs = nl2br($frontLogs);

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
            'serverAccessLogs' => $serverAccessLogs,
            'serverErrorLogs' => $serverErrorLogs,
        ], false);
    }
    public function actionGeneratePassword($password)
    {
        return Yii::$app
            ->getSecurity()
            ->generatePasswordHash($password);
    }
    public function actionClearLog()
    {
        if (file_put_contents(__DIR__ . '/../runtime/logs/app.log', '')) {
            return 'ok';
        }
        return 'error';
    }

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
                'message' => 'Logs prepended successfully'
            ];
        } else {
            $response->statusCode = 500;
            $response->data = [
                'status' => 'error',
                'message' => 'Failed to prepend logs'
            ];
        }
        return $response;
    }
    public function actionFetchChats()
    {
        $client = new Client(
            $_ENV['TWILIO_ACCOUNT_SID'],
            $_ENV['TWILIO_AUTH_TOKEN']
        );

        $chatSid = 'CH2c3f2f19547d46d08afb084eaa9256b6';
        $conversation = $client->conversations->v1->conversations($chatSid)->fetch();
        $participiants = $client->conversations->v1->conversations($chatSid)->participants->read();
        return $participiants;
    }
    public function actionResetApp()
    {
        //define vars 
        $db = Yii::$app->db;
        $apiCodes = Order::apiCodes();
        $attachmentsRoot = Yii::getAlias('@webroot/attachments');
        $files = scandir($attachmentsRoot);

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
            'privacy_policy',
            'product',
            'product_inspection_report',
            'product_link_attachment',
            'product_stock_report',
            'product_stock_report_link_attachment',
            // 'rate',
            // 'subcategory',
            // 'type_delivery',
            // 'type_delivery_link_category',
            // 'type_delivery_link_subcategory',
            // 'type_delivery_point',
            // 'type_delivery_price',
            // 'type_packaging',
            // 'user',
            'user_link_category',
            'user_link_type_delivery',
            'user_link_type_packaging',
            // 'user_settings',
            'user_verification_request',
        ];
        try {
            $db->createCommand('SET FOREIGN_KEY_CHECKS = 0;')->execute();
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    unlink($attachmentsRoot . '/' . $file);
                }
            }

            foreach ($tables as $table) {
                $db->createCommand()->truncateTable($table)->execute();
            }
            $db->createCommand('SET FOREIGN_KEY_CHECKS = 1;')->execute();
            return ApiResponse::byResponseCode($apiCodes->SUCCESS, ['message' => 'App reset successfully']);
        } catch (\yii\db\Exception $e) {
            return ApiResponse::byResponseCode($apiCodes->INTERNAL_ERROR, ['message' => $e->getMessage()]);
        }
    }
    public function actionUpdateImageSizes()
    {
        $output = [];
        // "/usr/local/var/www/joy_city/entrypoint/api/attachments"
        $attachmentsRoot = Yii::getAlias('@webroot/attachments');


        // get list of all product link attachments
        $attachLinks = \app\models\ProductLinkAttachment::find()->all();

        // get list of all image sizes
        $imageSizes = [
            ['width' => 256, 'height' => 256, 'name' => 'small'],
            ['width' => 512, 'height' => 512, 'name' => 'medium'],
            ['width' => 1024, 'height' => 1024, 'name' => 'large'],
        ];

        // create image manager with gd driver
        $imageManager = new ImageManager(new GdDriver());

        // loop through all product link attachments
        foreach ($attachLinks as $attachLink) {
            if ($attachLink->attachment->img_size == null) {
                // get product id
                $product_id = $attachLink->product_id;
                // get attachment
                $currentAttachment = $attachLink->attachment;

                //
                $output[] = $currentAttachment->path;

                //loop through all image sizes
                foreach ($imageSizes as $imageSize) {
                    // get image path
                    $imagePath = $attachmentsRoot . '/' . $currentAttachment->path;
                    //

                }
            }
        }
        return $output;
    }
}
