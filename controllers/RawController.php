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

use app\services\ExchangeRateService;

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
        // $frontLogs = implode("\n", array_slice(explode("\n", $frontLogs), 0, 2000));

        // format logs content
        $logs = nl2br($logs);
        // $frontLogs = nl2br($frontLogs);

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
    public function actionCreateChat()
    {
        $client = new Client(
            $_ENV['TWILIO_ACCOUNT_SID'],
            $_ENV['TWILIO_AUTH_TOKEN']
        );

        $conversation = $client->conversations->v1->conversations->read();
        foreach ($conversation as $conv) {
            var_dump($conv->links);
        }
    }

    public function actionAddParentCategories()
    {
        $rootCategories = [
            ['id' => 1, 'en_name' => 'Home & Garden', 'ru_name' => 'Товары для дома', 'zh_name' => "家居与花园", "is_deleted" => 0, "parent_id" => null, 'avatar_id' => "4"], //1
            ['id' => 2, 'en_name' => 'Clothing', 'ru_name' => 'Одежда', 'zh_name' => "服装", "is_deleted" => 0, "parent_id" => null, 'avatar_id' => "4"], //2
            ['id' => 3, 'en_name' => 'Electronics', 'ru_name' => 'Электроника', 'zh_name' => "电子产品", "is_deleted" => 0, "parent_id" => null, 'avatar_id' => "4"], //3
            ['id' => 4, 'en_name' => 'Furniture', 'ru_name' => 'Мебель', 'zh_name' => "家具", "is_deleted" => 0, "parent_id" => null, 'avatar_id' => "4"], //4
            ['id' => 5, 'en_name' => 'Toys', 'ru_name' => 'Игрушки', 'zh_name' => "玩具", "is_deleted" => 0, "parent_id" => null, 'avatar_id' => "4"], //5
            ['id' => 6, 'en_name' => 'Sports', 'ru_name' => 'Спорт', 'zh_name' => "体育", "is_deleted" => 0, "parent_id" => null, 'avatar_id' => "4"], //6
            ['id' => 7, 'en_name' => 'Automotive', 'ru_name' => 'Автомобили', 'zh_name' => "汽车", "is_deleted" => 0, "parent_id" => null, 'avatar_id' => "4"],
        ];

        $categories = [
            'Home & Garden' => [
                // Home & Garden
                ['id' => 8, 'en_name' => 'Plants', 'ru_name' => 'Растения', 'zh_name' => "植物", "is_deleted" => 0, "parent_id" => 1, 'avatar_id' => "4"],
                ['id' => 9, 'en_name' => 'Fertilizers', 'ru_name' => 'Удобрения', 'zh_name' => "肥料", "is_deleted" => 0, "parent_id" => 1, 'avatar_id' => "4"],
                ['id' => 10, 'en_name' => 'Seeds', 'ru_name' => 'Семена', 'zh_name' => "种子", "is_deleted" => 0, "parent_id" => 1, 'avatar_id' => "4"],
                ['id' => 11, 'en_name' => 'Tools', 'ru_name' => 'Садовые инструменты', 'zh_name' => "园艺工具", "is_deleted" => 0, "parent_id" => 1, 'avatar_id' => "4"],
            ],

            'Clothing' => [
                // Clothing
                ['id' => 12, 'en_name' => 'Men', 'ru_name' => 'Мужская одежда', 'zh_name' => "男装", "is_deleted" => 0, "parent_id" => 2, 'avatar_id' => "4"],
                ['id' => 13, 'en_name' => 'Women', 'ru_name' => 'Женская одежда', 'zh_name' => "女装", "is_deleted" => 0, "parent_id" => 2, 'avatar_id' => "4"],
                ['id' => 14, 'en_name' => 'Children', 'ru_name' => 'Детская одежда', 'zh_name' => "儿童服装", "is_deleted" => 0, "parent_id" => 2, 'avatar_id' => "4"],
                ['id' => 15, 'en_name' => 'Baby', 'ru_name' => 'Одежда для малышей', 'zh_name' => "婴儿服装", "is_deleted" => 0, "parent_id" => 2, 'avatar_id' => "4"],
            ],

            'Electronics' => [
                // Electronics
                ['id' => 16, 'en_name' => 'Smartphones', 'ru_name' => 'Смартфоны', 'zh_name' => "智能手机", "is_deleted" => 0, "parent_id" => 3, 'avatar_id' => "4"],
                ['id' => 17, 'en_name' => 'Laptops', 'ru_name' => 'Ноутбуки', 'zh_name' => "笔记本电脑", "is_deleted" => 0, "parent_id" => 3, 'avatar_id' => "4"],
                ['id' => 18, 'en_name' => 'Tablets', 'ru_name' => 'Планшеты', 'zh_name' => "平板电脑", "is_deleted" => 0, "parent_id" => 3, 'avatar_id' => "4"],
                ['id' => 19, 'en_name' => 'Smartwatches', 'ru_name' => 'Умные часы', 'zh_name' => "智能手表", "is_deleted" => 0, "parent_id" => 3, 'avatar_id' => "4"],
            ],
            'Furniture' => [
                // Furniture
                ['id' => 20, 'en_name' => 'Beds', 'ru_name' => 'Мебель для дома', 'zh_name' => "家居家具", "is_deleted" => 0, "parent_id" => 4, 'avatar_id' => "4"],
                ['id' => 21, 'en_name' => 'Sofas', 'ru_name' => 'Уличная мебель', 'zh_name' => "户外家具", "is_deleted" => 0, "parent_id" => 4, 'avatar_id' => "4"],
                ['id' => 22, 'en_name' => 'Kitchen', 'ru_name' => 'Мебель для кухни', 'zh_name' => "厨房家具", "is_deleted" => 0, "parent_id" => 4, 'avatar_id' => "4"],
                ['id' => 23, 'en_name' => 'Office', 'ru_name' => 'Мебель для офиса', 'zh_name' => "办公室家具", "is_deleted" => 0, "parent_id" => 4, 'avatar_id' => "4"],
            ],
            'Toys' => [
                // Toys
                ['id' => 24, 'en_name' => 'Baby', 'ru_name' => 'Игрушки для малышей', 'zh_name' => "婴儿玩具", "is_deleted" => 0, "parent_id" => 5, 'avatar_id' => "4"],
                ['id' => 25, 'en_name' => 'Kids', 'ru_name' => 'Детские игрушки', 'zh_name' => "儿童玩具", "is_deleted" => 0, "parent_id" => 5, 'avatar_id' => "4"],
                ['id' => 26, 'en_name' => 'Puzzles', 'ru_name' => 'Головоломки', 'zh_name' => "益智玩具", "is_deleted" => 0, "parent_id" => 5, 'avatar_id' => "4"],
                ['id' => 27, 'en_name' => 'Building', 'ru_name' => 'Конструкторы', 'zh_name' => "积木玩具", "is_deleted" => 0, "parent_id" => 5, 'avatar_id' => "4"],
            ],
            'Sports' => [
                // Sports
                ['id' => 28, 'en_name' => 'Balls', 'ru_name' => 'Мячи', 'zh_name' => "球类", "is_deleted" => 0, "parent_id" => 6, 'avatar_id' => "4"],
                ['id' => 29, 'en_name' => 'Rackets', 'ru_name' => 'Ракетки', 'zh_name' => "球拍", "is_deleted" => 0, "parent_id" => 6, 'avatar_id' => "4"],
                ['id' => 30, 'en_name' => 'Fitness', 'ru_name' => 'Фитнес', 'zh_name' => "健身", "is_deleted" => 0, "parent_id" => 6, 'avatar_id' => "4"],
            ],
            'Automotive' => [
                // Automotive
                ['id' => 31, 'en_name' => 'Parts', 'ru_name' => 'Запчасти', 'zh_name' => "汽车配件", "is_deleted" => 0, "parent_id" => 7, 'avatar_id' => "4"],
                ['id' => 32, 'en_name' => 'Accessories', 'ru_name' => 'Аксессуары', 'zh_name' => "配件", "is_deleted" => 0, "parent_id" => 7, 'avatar_id' => "4"],
                ['id' => 33, 'en_name' => 'Radio', 'ru_name' => 'Магнитолы', 'zh_name' => "车载音响", "is_deleted" => 0, "parent_id" => 7, 'avatar_id' => "4"],
                ['id' => 34, 'en_name' => 'Batteries', 'ru_name' => 'Аккумуляторы', 'zh_name' => "蓄电池", "is_deleted" => 0, "parent_id" => 7, 'avatar_id' => "4"],
            ],
        ];

        $subcategories = [
            'Plants' => [
                // Plants
                ['id' => 35, 'en_name' => 'Vegetables', 'ru_name' => 'Овощи', 'zh_name' => "蔬菜", "is_deleted" => 0, "parent_id" => 8, 'avatar_id' => "4"],
                ['id' => 36, 'en_name' => 'Fruits', 'ru_name' => 'Фрукты', 'zh_name' => "水果", "is_deleted" => 0, "parent_id" => 8, 'avatar_id' => "4"],
                ['id' => 37, 'en_name' => 'Flowers', 'ru_name' => 'Цветы', 'zh_name' => "花卉", "is_deleted" => 0, "parent_id" => 8, 'avatar_id' => "4"],
                ['id' => 38, 'en_name' => 'Trees', 'ru_name' => 'Деревья', 'zh_name' => "树木", "is_deleted" => 0, "parent_id" => 8, 'avatar_id' => "4"],

            ],
            'Men' => [
                // Men
                ['id' => 32, 'en_name' => 'Men\'s Shirts', 'ru_name' => 'Рубашки для мужчин', 'zh_name' => "男士衬衫", "is_deleted" => 0, "parent_id" => 8, 'avatar_id' => "4"],
                ['id' => 33, 'en_name' => 'Men\'s Pants', 'ru_name' => 'Брюки для мужчин', 'zh_name' => "男士裤子", "is_deleted" => 0, "parent_id" => 8, 'avatar_id' => "4"],
                ['id' => 34, 'en_name' => 'Men\'s Jackets', 'ru_name' => 'Куртки для мужчин', 'zh_name' => "男士夹克", "is_deleted" => 0, "parent_id" => 8, 'avatar_id' => "4"],
                ['id' => 35, 'en_name' => 'Men\'s Shoes', 'ru_name' => 'Обувь для мужчин', 'zh_name' => "男士鞋", "is_deleted" => 0, "parent_id" => 8, 'avatar_id' => "4"],
            ],
            'Women' => [
                // Women
                ['id' => 37, 'en_name' => 'Women\'s Tops', 'ru_name' => 'Топы для женщин', 'zh_name' => "女装上衣", "is_deleted" => 0, "parent_id" => 9, 'avatar_id' => "4"],
                ['id' => 38, 'en_name' => 'Women\'s Skirts', 'ru_name' => 'Юбки для женщин', 'zh_name' => "女装裙子", "is_deleted" => 0, "parent_id" => 9, 'avatar_id' => "4"],
                ['id' => 39, 'en_name' => 'Women\'s Shoes', 'ru_name' => 'Обувь для женщин', 'zh_name' => "女鞋", "is_deleted" => 0, "parent_id" => 9, 'avatar_id' => "4"],
                ['id' => 40, 'en_name' => 'Women\'s Accessories', 'ru_name' => 'Аксессуары для женщин', 'zh_name' => "女装配件", "is_deleted" => 0, "parent_id" => 9, 'avatar_id' => "4"],
            ],
            'Children' => [
                // Children
                ['id' => 40, 'en_name' => 'Children\'s Clothing', 'ru_name' => 'Одежда для детей', 'zh_name' => "儿童服装", "is_deleted" => 0, "parent_id" => 10, 'avatar_id' => "4"],
                ['id' => 41, 'en_name' => 'Children\'s Shoes', 'ru_name' => 'Обувь для детей', 'zh_name' => "儿童鞋", "is_deleted" => 0, "parent_id" => 10, 'avatar_id' => "4"],
                ['id' => 42, 'en_name' => 'Children\'s Accessories', 'ru_name' => 'Аксессуары для детей', 'zh_name' => "儿童配件", "is_deleted" => 0, "parent_id" => 10, 'avatar_id' => "4"],
                ['id' => 43, 'en_name' => 'Children\'s Toys', 'ru_name' => 'Игрушки для детей', 'zh_name' => "儿童玩具", "is_deleted" => 0, "parent_id" => 10, 'avatar_id' => "4"],
            ],
            'Baby' => [
                // Baby
                ['id' => 44, 'en_name' => 'Baby Clothing', 'ru_name' => 'Одежда для малышей', 'zh_name' => "婴儿服装", "is_deleted" => 0, "parent_id" => 11, 'avatar_id' => "4"],
                ['id' => 45, 'en_name' => 'Baby Shoes', 'ru_name' => 'Обувь для малышей', 'zh_name' => "婴儿鞋", "is_deleted" => 0, "parent_id" => 11, 'avatar_id' => "4"],
                ['id' => 46, 'en_name' => 'Baby Accessories', 'ru_name' => 'Аксессуары для малышей', 'zh_name' => "婴儿配件", "is_deleted" => 0, "parent_id" => 11, 'avatar_id' => "4"],
                ['id' => 47, 'en_name' => 'Baby Toys', 'ru_name' => 'Игрушки для малышей', 'zh_name' => "婴儿玩具", "is_deleted" => 0, "parent_id" => 11, 'avatar_id' => "4"],
            ],
            'Smartphones' => [
                // Electronics
                ['id' => 49, 'en_name' => 'Laptops', 'ru_name' => 'Ноутбуки', 'zh_name' => "笔记本电脑", "is_deleted" => 0, "parent_id" => 12, 'avatar_id' => "4"],
                ['id' => 50, 'en_name' => 'Tablets', 'ru_name' => 'Планшеты', 'zh_name' => "平板电脑", "is_deleted" => 0, "parent_id" => 12, 'avatar_id' => "4"],
                ['id' => 51, 'en_name' => 'Smartwatches', 'ru_name' => 'Умные часы', 'zh_name' => "智能手表", "is_deleted" => 0, "parent_id" => 12, 'avatar_id' => "4"],
            ],
            'Furniture' => [
                ['id' => 52, 'en_name' => 'Beds', 'ru_name' => 'Кровати', 'zh_name' => "床", "is_deleted" => 0, "parent_id" => 16, 'avatar_id' => "4"],
                ['id' => 53, 'en_name' => 'Sofas', 'ru_name' => 'Диваны', 'zh_name' => "沙发", "is_deleted" => 0, "parent_id" => 16, 'avatar_id' => "4"],
                ['id' => 54, 'en_name' => 'Tables', 'ru_name' => 'Столы', 'zh_name' => "桌子", "is_deleted" => 0, "parent_id" => 16, 'avatar_id' => "4"],
                ['id' => 55, 'en_name' => 'Chairs', 'ru_name' => 'Стулья', 'zh_name' => "椅子", "is_deleted" => 0, "parent_id" => 16, 'avatar_id' => "4"],
            ],
            'Toys' => [
                // Toys
                ['id' => 56, 'en_name' => 'Action Figures', 'ru_name' => 'Фигурки', 'zh_name' => "动作玩偶", "is_deleted" => 0, "parent_id" => 20, 'avatar_id' => "4"],
                ['id' => 57, 'en_name' => 'Board Games', 'ru_name' => 'Настольные игры', 'zh_name' => "桌游", "is_deleted" => 0, "parent_id" => 20, 'avatar_id' => "4"],
                ['id' => 58, 'en_name' => 'Stuffed Animals', 'ru_name' => 'Плюшевые игрушки', 'zh_name' => "毛绒玩具", "is_deleted" => 0, "parent_id" => 20, 'avatar_id' => "4"],
                ['id' => 59, 'en_name' => 'Building Blocks', 'ru_name' => 'Конструкторы', 'zh_name' => "积木", "is_deleted" => 0, "parent_id" => 20, 'avatar_id' => "4"],
            ],
            'Sports' => [
                // Sports
                ['id' => 61, 'en_name' => 'Rackets', 'ru_name' => 'Ракетки', 'zh_name' => "球拍", "is_deleted" => 0, "parent_id" => 24, 'avatar_id' => "4"],
                ['id' => 62, 'en_name' => 'Fitness Equipment', 'ru_name' => 'Фитнес-оборудование', 'zh_name' => "健身器材", "is_deleted" => 0, "parent_id" => 24, 'avatar_id' => "4"],
                ['id' => 63, 'en_name' => 'Sports Apparel', 'ru_name' => 'Спортивная одежда', 'zh_name' => "运动服装", "is_deleted" => 0, "parent_id" => 24, 'avatar_id' => "4"],
            ],
            'Automotive' => [
                ['id' => 64, 'en_name' => 'Car Parts', 'ru_name' => 'Автозапчасти', 'zh_name' => "汽车配件", "is_deleted" => 0, "parent_id" => 28, 'avatar_id' => "4"],
                ['id' => 65, 'en_name' => 'Car Accessories', 'ru_name' => 'Автоаксессуары', 'zh_name' => "汽车配件", "is_deleted" => 0, "parent_id" => 28, 'avatar_id' => "4"],
                ['id' => 66, 'en_name' => 'Car Electronics', 'ru_name' => 'Автоэлектроника', 'zh_name' => "汽车电子", "is_deleted" => 0, "parent_id" => 28, 'avatar_id' => "4"],
                ['id' => 67, 'en_name' => 'Car Maintenance', 'ru_name' => 'Обслуживание автомобиля', 'zh_name' => "汽车维护", "is_deleted" => 0, "parent_id" => 28, 'avatar_id' => "4"],
            ],
        ];

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Step 2: Create root categories
            foreach ($rootCategories as $rootCategoryData) { // Changed variable name
                $rootCategory = new \app\models\Category();
                $rootCategory->attributes = $rootCategoryData; // Use the new variable

                if (!$rootCategory->save()) {
                    throw new \Exception('Failed to save root category: ' . implode(', ', $rootCategory->getFirstErrors()));
                }

                // Step 3: Create categories with parent ID
                foreach ($categories[$rootCategory->en_name] as $categoryData) { // Changed variable name
                    $category = new \app\models\Category();
                    $category->attributes = $categoryData; // Use the new variable
                    $category->parent_id = $rootCategory->id; // Set parent ID

                    if (!$category->save()) {
                        throw new \Exception('Failed to save category: ' . implode(', ', $category->getFirstErrors()));
                    }

                    // Step 4: Create subcategories
                    if (isset($subcategories[$category->en_name])) {
                        foreach ($subcategories[$category->en_name] as $subcategoryData) { // Changed variable name
                            $subcategory = new \app\models\Category();
                            $subcategory->attributes = $subcategoryData; // Use the new variable
                            $subcategory->parent_id = $category->id; // Set parent ID

                            if (!$subcategory->save()) {
                                throw new \Exception('Failed to save subcategory: ' . implode(', ', $subcategory->getFirstErrors()));
                            }
                        }
                    }
                }
            }

            // Commit the transaction
            $transaction->commit();
        } catch (\Exception $e) {
            // Rollback the transaction if something went wrong
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);
            throw $e; // Optionally rethrow the exception
        }

        return [
            'status' => 'success',
            'message' => 'Categories added successfully',
        ];
    }
}
