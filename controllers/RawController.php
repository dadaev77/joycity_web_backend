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
        $logs = file_exists(self::LOG_FILE) ? file_get_contents(self::LOG_FILE) : 'Файл лога не найден';
        $frontLogs = file_exists(self::FRONT_LOG_FILE) ? file_get_contents(self::FRONT_LOG_FILE) : 'Файл фронт-логов не найден';
        $actionLogs = file_exists(self::ACTION_LOG_FILE) ? file_get_contents(self::ACTION_LOG_FILE) : 'Файл логов действий не найден';
        $serverAccessLogs = 'Файл логов доступа сервера не найден';
        $serverErrorLogs = 'Файл логов ошибок сервера не найден';
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
        var_dump($conversations);
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
    // public function actionNewCatalog()
    // {
    //     //
    //     $data = [
    //         // Root categories (1st level)
    //         ['id' => 1, 'en_name' => 'All', 'ru_name' => 'Все', 'zh_name' => '全部', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => null],
    //         // Second level categories
    //         ['id' => 2, 'en_name' => 'Women', 'ru_name' => 'Женщинам', 'zh_name' => '女性', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 3, 'en_name' => 'Footwear', 'ru_name' => 'Обувь', 'zh_name' => '鞋', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 4, 'en_name' => 'Children', 'ru_name' => 'Детям', 'zh_name' => '兒童用', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 5, 'en_name' => 'Men', 'ru_name' => 'Мужчинам', 'zh_name' => '男士用', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 6, 'en_name' => 'Home', 'ru_name' => 'Дом', 'zh_name' => '房子', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 7, 'en_name' => 'Beauty', 'ru_name' => 'Красота', 'zh_name' => '美麗', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 8, 'en_name' => 'Accessories', 'ru_name' => 'Аксессуары', 'zh_name' => '配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 9, 'en_name' => 'Electronics', 'ru_name' => 'Электроника', 'zh_name' => '電子產品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 10, 'en_name' => 'Toys', 'ru_name' => 'Игрушки', 'zh_name' => '玩具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 11, 'en_name' => 'Furniture', 'ru_name' => 'Мебель', 'zh_name' => '家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 12, 'en_name' => 'Household appliances', 'ru_name' => 'Бытовая техника', 'zh_name' => '家用電器', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 13, 'en_name' => 'Pet supplies', 'ru_name' => 'Зоотовары', 'zh_name' => '寵物用品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 14, 'en_name' => 'Sports', 'ru_name' => 'Спорт', 'zh_name' => '運動', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 15, 'en_name' => 'Car products', 'ru_name' => 'Автотовары', 'zh_name' => '汽車產品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 16, 'en_name' => 'Books', 'ru_name' => 'Книги', 'zh_name' => '圖書', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 17, 'en_name' => 'Jewelry', 'ru_name' => 'Ювелирные изделия', 'zh_name' => '珠寶', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 18, 'en_name' => 'For repairs', 'ru_name' => 'Для ремонта', 'zh_name' => '維修用', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 19, 'en_name' => 'Garden and summer cottage', 'ru_name' => 'Сад и дача', 'zh_name' => '花園和別墅', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 20, 'en_name' => 'Health', 'ru_name' => 'Здоровье', 'zh_name' => '健康', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         ['id' => 21, 'en_name' => 'Stationery', 'ru_name' => 'Канцтовары', 'zh_name' => '靜止的', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 1],
    //         // Third level categories
    //         // parent id = 2
    //         ['id' => 21, 'en_name' => 'Blouses and shirts', 'ru_name' => 'Блузки и рубашки', 'zh_name' => '襯衫和襯衫', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 22, 'en_name' => 'Trousers', 'ru_name' => 'Брюки', 'zh_name' => '褲子', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 23, 'en_name' => 'Outerwear', 'ru_name' => 'Верхняя одежда', 'zh_name' => '外套', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 24, 'en_name' => 'Jumpers, turtlenecks and cardigans', 'ru_name' => 'Джемперы, водолазки и кардиганы', 'zh_name' => '毛衣、高領毛衣和開襟衫', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 25, 'en_name' => 'Jeans', 'ru_name' => 'Джинсы', 'zh_name' => '牛仔褲', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 26, 'en_name' => 'Overalls', 'ru_name' => 'Комбинезоны', 'zh_name' => '連身褲', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 27, 'en_name' => 'Suits', 'ru_name' => 'Костюмы', 'zh_name' => '套裝', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 28, 'en_name' => 'Longsleeves', 'ru_name' => 'Лонгсливы', 'zh_name' => '長袖', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 29, 'en_name' => 'Jackets, vests and jackets', 'ru_name' => 'Пиджаки, жилеты и жакеты', 'zh_name' => '西裝外套、背心和夾克', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 30, 'en_name' => 'Dresses and sundresses', 'ru_name' => 'Платья и сарафаны', 'zh_name' => '洋裝和背心裙', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 31, 'en_name' => 'Sweatshirts, sweatshirts and hoodies', 'ru_name' => 'Толстовки, свитшоты и худи', 'zh_name' => '運動衫、運動衫和連帽衫', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 32, 'en_name' => 'Tunics', 'ru_name' => 'Туники', 'zh_name' => '束腰外衣', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 33, 'en_name' => 'T-shirts and tops', 'ru_name' => 'Футболки и топы', 'zh_name' => 'T 恤和上衣', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 34, 'en_name' => 'Robes', 'ru_name' => 'Халаты', 'zh_name' => '長袍', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 35, 'en_name' => 'Shorts', 'ru_name' => 'Шорты', 'zh_name' => '短褲', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 36, 'en_name' => 'Skirts', 'ru_name' => 'Юбки', 'zh_name' => '裙子', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 37, 'en_name' => 'Underwear', 'ru_name' => 'Белье', 'zh_name' => '內衣', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 38, 'en_name' => 'Plus sizes', 'ru_name' => 'Большие размеры', 'zh_name' => '大尺寸', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 39, 'en_name' => 'Expectant mothers', 'ru_name' => 'Будущие мамы', 'zh_name' => '未來的母親', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 40, 'en_name' => 'For tall', 'ru_name' => 'Для высоких', 'zh_name' => '對於高個子', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 41, 'en_name' => 'For short', 'ru_name' => 'Для невысоких', 'zh_name' => '對於個子矮的人來說', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 42, 'en_name' => 'Homewear', 'ru_name' => 'Одежда для дома', 'zh_name' => '家居服', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 43, 'en_name' => 'Office', 'ru_name' => 'Офис', 'zh_name' => '辦公室', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 44, 'en_name' => 'Beach fashion', 'ru_name' => 'Пляжная мода', 'zh_name' => '海灘時尚', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 45, 'en_name' => 'Religious', 'ru_name' => 'Религиозная', 'zh_name' => '宗教', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 46, 'en_name' => 'Wedding', 'ru_name' => 'Свадьба', 'zh_name' => '婚禮', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 47, 'en_name' => 'Workwear and PPE', 'ru_name' => 'Спецодежда и СИЗы', 'zh_name' => '工作服和個人防護裝備', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         ['id' => 48, 'en_name' => 'Gifts for women', 'ru_name' => 'Подарки женщинам', 'zh_name' => '給女士的禮物', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 2],
    //         // parent id = 3
    //         ['id' => 49, 'en_name' => 'Children', 'ru_name' => 'Детская', 'zh_name' => '孩子們的', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 3],
    //         ['id' => 50, 'en_name' => 'For newborns', 'ru_name' => 'Для новорожденных', 'zh_name' => '對於新生兒', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 3],
    //         ['id' => 51, 'en_name' => "Women's", 'ru_name' => 'Женская', 'zh_name' => '女性的', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 3],
    //         ['id' => 52, 'en_name' => "Men's", 'ru_name' => 'Мужская', 'zh_name' => '男士', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 3],
    //         ['id' => 53, 'en_name' => 'Safety footwear', 'ru_name' => 'Спецобувь', 'zh_name' => '安全鞋', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 3],
    //         ['id' => 54, 'en_name' => 'Accessories for footwear', 'ru_name' => 'Аксессуары для обуви', 'zh_name' => '鞋材配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 3],
    //         // parent id = 4
    //         ['id' => 55, 'en_name' => 'For girls', 'ru_name' => 'Для девочек', 'zh_name' => '適合女孩', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 4],
    //         ['id' => 56, 'en_name' => 'For boys', 'ru_name' => 'Для мальчиков', 'zh_name' => '適合男孩', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 4],
    //         ['id' => 57, 'en_name' => 'For newborns', 'ru_name' => 'Для новорожденных', 'zh_name' => '對於新生兒', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 4],
    //         ['id' => 58, 'en_name' => "Children's electronics", 'ru_name' => 'Детская электроника', 'zh_name' => '兒童電子產品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 4],
    //         ['id' => 59, 'en_name' => 'Constructors', 'ru_name' => 'Конструкторы', 'zh_name' => '建構函數', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 4],
    //         ['id' => 60, 'en_name' => "Children's transport", 'ru_name' => 'Детский транспорт', 'zh_name' => '兒童交通', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 4],
    //         ['id' => 61, 'en_name' => 'Walks and travel', 'ru_name' => 'Прогулки и путешествия', 'zh_name' => '散步和旅行', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 4],
    //         ['id' => 62, 'en_name' => 'Baby food', 'ru_name' => 'Детское питание', 'zh_name' => '嬰兒食品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 4],
    //         ['id' => 63, 'en_name' => 'Religious clothing', 'ru_name' => 'Религиозная одежда', 'zh_name' => '宗教服裝', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 4],
    //         ['id' => 64, 'en_name' => 'Goods for babies', 'ru_name' => 'Товары для малыша', 'zh_name' => '嬰兒用品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 4],
    //         ['id' => 65, 'en_name' => 'Diapers', 'ru_name' => 'Подгузники', 'zh_name' => '尿布', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 4],
    //         ['id' => 66, 'en_name' => 'Gifts for children', 'ru_name' => 'Подарки детям', 'zh_name' => '給孩子的禮物', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 4],
    //         // parent id = 5
    //         ['id' => 67, 'en_name' => 'Trousers', 'ru_name' => 'Брюки', 'zh_name' => '褲子', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 68, 'en_name' => 'Outerwear', 'ru_name' => 'Верхняя одежда', 'zh_name' => '外套', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 69, 'en_name' => 'Jumpers, turtlenecks and cardigans', 'ru_name' => 'Джемперы, водолазки и кардиганы', 'zh_name' => '毛衣、高領毛衣和開襟衫', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 70, 'en_name' => 'Jeans', 'ru_name' => 'Джинсы', 'zh_name' => '牛仔褲', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 71, 'en_name' => 'Overalls and semi-overalls', 'ru_name' => 'Комбинезоны и полукомбинезоны', 'zh_name' => '工作服和背帶褲', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 72, 'en_name' => 'Suits', 'ru_name' => 'Костюмы', 'zh_name' => '套裝', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 73, 'en_name' => 'Longsleeves', 'ru_name' => 'Лонгсливы', 'zh_name' => '長袖', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 74, 'en_name' => 'T-shirts', 'ru_name' => 'Майки', 'zh_name' => '米奇', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 75, 'en_name' => 'Jackets, vests and jackets', 'ru_name' => 'Пиджаки, жилеты и жакеты', 'zh_name' => '西裝外套、背心和夾克', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 76, 'en_name' => 'Pajamas', 'ru_name' => 'Пижамы', 'zh_name' => '睡衣', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 77, 'en_name' => 'Shirts', 'ru_name' => 'Рубашки', 'zh_name' => '襯衫', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 78, 'en_name' => 'Sweatshirts, sweatshirts and hoodies', 'ru_name' => 'Толстовки, свитшоты и худи', 'zh_name' => '運動衫、運動衫和連帽衫', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 79, 'en_name' => 'T-shirts', 'ru_name' => 'Футболки', 'zh_name' => 'T恤', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 80, 'en_name' => 'Polo shirts', 'ru_name' => 'Футболки-поло', 'zh_name' => 'Polo 衫', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 81, 'en_name' => 'Robes', 'ru_name' => 'Халаты', 'zh_name' => '長袍', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 82, 'en_name' => 'Shorts', 'ru_name' => 'Шорты', 'zh_name' => '短褲', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 83, 'en_name' => 'Underwear', 'ru_name' => 'Белье', 'zh_name' => '內衣', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 84, 'en_name' => 'Plus sizes', 'ru_name' => 'Большие размеры', 'zh_name' => '大尺寸', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 85, 'en_name' => 'For tall', 'ru_name' => 'Для высоких', 'zh_name' => '對於高個子', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 86, 'en_name' => 'For short', 'ru_name' => 'Для невысоких', 'zh_name' => '對於個子矮的人來說', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 87, 'en_name' => 'Homewear', 'ru_name' => 'Одежда для дома', 'zh_name' => '家居服', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 88, 'en_name' => 'Office', 'ru_name' => 'Офис', 'zh_name' => '辦公室', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 89, 'en_name' => 'Beach clothing', 'ru_name' => 'Пляжная одежда', 'zh_name' => '沙灘裝', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 90, 'en_name' => 'Religious', 'ru_name' => 'Религиозная', 'zh_name' => '宗教', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 91, 'en_name' => 'Wedding', 'ru_name' => 'Свадьба', 'zh_name' => '婚禮', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 92, 'en_name' => 'Workwear and PPE', 'ru_name' => 'Спецодежда и СИЗы', 'zh_name' => '工作服和個人防護裝備', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         ['id' => 93, 'en_name' => 'Gifts for men', 'ru_name' => 'Подарки мужчинам', 'zh_name' => '給男士的禮物', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 5],
    //         // parent id = 6
    //         ['id' => 94, 'en_name' => 'Bathroom', 'ru_name' => 'Ванная', 'zh_name' => '浴室', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 95, 'en_name' => 'Kitchen', 'ru_name' => 'Кухня', 'zh_name' => '廚房', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 96, 'en_name' => 'Interior items', 'ru_name' => 'Предметы интерьера', 'zh_name' => '室內裝潢用品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 97, 'en_name' => 'Bedroom', 'ru_name' => 'Спальня', 'zh_name' => '臥室', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 98, 'en_name' => 'Living room', 'ru_name' => 'Гостиная', 'zh_name' => '客廳', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 99, 'en_name' => "Children's", 'ru_name' => 'Детская', 'zh_name' => '孩子們的', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 100, 'en_name' => 'Leisure and creativity', 'ru_name' => 'Досуг и творчество', 'zh_name' => '休閒與創意', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 101, 'en_name' => 'Everything for the holiday', 'ru_name' => 'Все для праздника', 'zh_name' => '一切都是為了假期', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 102, 'en_name' => 'Mirrors', 'ru_name' => 'Зеркала', 'zh_name' => '鏡子', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 103, 'en_name' => 'Rugs', 'ru_name' => 'Коврики', 'zh_name' => '地毯', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 104, 'en_name' => 'Brackets', 'ru_name' => 'Кронштейны', 'zh_name' => '括號', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 105, 'en_name' => 'Lighting', 'ru_name' => 'Освещение', 'zh_name' => '燈光', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 106, 'en_name' => 'For smoking', 'ru_name' => 'Для курения', 'zh_name' => '吸煙用', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 107, 'en_name' => 'Outdoor recreation', 'ru_name' => 'Отдых на природе', 'zh_name' => '戶外休閒', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 108, 'en_name' => 'Perfumery for the home', 'ru_name' => 'Парфюмерия для дома', 'zh_name' => '家用香水', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 109, 'en_name' => 'Hallway', 'ru_name' => 'Прихожая', 'zh_name' => '門廳', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 110, 'en_name' => 'Religion, esotericism', 'ru_name' => 'Религия, эзотерика', 'zh_name' => '宗教、深奧', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 111, 'en_name' => 'Souvenir products', 'ru_name' => 'Сувенирная продукция', 'zh_name' => '旅遊紀念品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 112, 'en_name' => 'Household goods', 'ru_name' => 'Хозяйственные товары', 'zh_name' => '家居用品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 113, 'en_name' => 'Storage of things', 'ru_name' => 'Хранение вещей', 'zh_name' => '存放東西', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 114, 'en_name' => 'Flowers, vases and planters', 'ru_name' => 'Цветы, вазы и кашпо', 'zh_name' => '鮮花、花瓶和花盆', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         ['id' => 115, 'en_name' => 'Curtains', 'ru_name' => 'Шторы', 'zh_name' => '窗簾', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 6],
    //         // parent id = 7
    //         ['id' => 116, 'en_name' => 'Accessories', 'ru_name' => 'Аксессуары', 'zh_name' => '配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 117, 'en_name' => 'Hair', 'ru_name' => 'Волосы', 'zh_name' => '頭髮', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 118, 'en_name' => 'Pharmacy cosmetics', 'ru_name' => 'Аптечная косметика', 'zh_name' => '藥房化妝品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 119, 'en_name' => "Children's decorative cosmetics", 'ru_name' => 'Детская декоративная косметика', 'zh_name' => '兒童裝飾化妝品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 120, 'en_name' => 'For tanning', 'ru_name' => 'Для загара', 'zh_name' => '用於曬黑', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 121, 'en_name' => 'For mothers and babies', 'ru_name' => 'Для мам и малышей', 'zh_name' => '給媽媽和寶寶', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 122, 'en_name' => 'Israeli cosmetics', 'ru_name' => 'Израильская косметика', 'zh_name' => '以色列化妝品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 123, 'en_name' => 'Tools for hairdressers', 'ru_name' => 'Инструменты для парикмахеров', 'zh_name' => '美髮師的工具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 124, 'en_name' => 'Korean brands', 'ru_name' => 'Корейские бренды', 'zh_name' => '韓國品牌', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 125, 'en_name' => 'Cosmetic devices and accessories', 'ru_name' => 'Косметические аппараты и аксессуары', 'zh_name' => '美容儀器及配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 126, 'en_name' => 'Crimean cosmetics', 'ru_name' => 'Крымская косметика', 'zh_name' => '克里米亞化妝品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 127, 'en_name' => 'Makeup', 'ru_name' => 'Макияж', 'zh_name' => '化妝品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 128, 'en_name' => "Men's line", 'ru_name' => 'Мужская линия', 'zh_name' => '男系', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 129, 'en_name' => 'Sets for care', 'ru_name' => 'Наборы для ухода', 'zh_name' => '護理套件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 130, 'en_name' => 'Nails', 'ru_name' => 'Ногти', 'zh_name' => '指甲', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 131, 'en_name' => 'Organic cosmetics', 'ru_name' => 'Органическая косметика', 'zh_name' => '有機化妝品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 132, 'en_name' => 'Perfumery', 'ru_name' => 'Парфюмерия', 'zh_name' => '香料', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 133, 'en_name' => 'Gift sets', 'ru_name' => 'Подарочные наборы', 'zh_name' => '禮品套裝', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 134, 'en_name' => 'Professional cosmetics', 'ru_name' => 'Профессиональная косметика', 'zh_name' => '專業化妝品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 135, 'en_name' => 'Personal hygiene', 'ru_name' => 'Средства личной гигиены', 'zh_name' => '個人護理產品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 136, 'en_name' => 'Oral hygiene', 'ru_name' => 'Гигиена полости рта', 'zh_name' => '口腔衛生', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 137, 'en_name' => 'Skin care', 'ru_name' => 'Уход за кожей', 'zh_name' => '皮膚護理', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 138, 'en_name' => 'Beauty salon furniture', 'ru_name' => 'Мебель для салонов красоты', 'zh_name' => '美容院家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         ['id' => 139, 'en_name' => 'Hair accessories', 'ru_name' => 'Аксессуары для волос', 'zh_name' => '髮飾', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 7],
    //         // parent id = 8
    //         ['id' => 140, 'en_name' => 'Clothing accessories', 'ru_name' => 'Аксессуары для одежды', 'zh_name' => '服裝輔料', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 141, 'en_name' => 'Costume jewelry', 'ru_name' => 'Бижутерия', 'zh_name' => '珠寶首飾', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 142, 'en_name' => 'Jewelry', 'ru_name' => 'Ювелирные изделия', 'zh_name' => '珠寶', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 143, 'en_name' => 'Fans', 'ru_name' => 'Веера', 'zh_name' => '粉絲', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 144, 'en_name' => 'Ties and bow ties', 'ru_name' => 'Галстуки и бабочки', 'zh_name' => '領帶和領結', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 145, 'en_name' => 'Headwear', 'ru_name' => 'Головные уборы', 'zh_name' => '帽子', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 146, 'en_name' => 'Mirrors', 'ru_name' => 'Зеркальца', 'zh_name' => '鏡子', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 147, 'en_name' => 'Umbrellas', 'ru_name' => 'Зонты', 'zh_name' => '雨傘', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 148, 'en_name' => 'Wallets and credit card holders', 'ru_name' => 'Кошельки и кредитницы', 'zh_name' => '錢包和信用持有人', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 149, 'en_name' => 'Sleep masks', 'ru_name' => 'Маски для сна', 'zh_name' => '睡眠面膜', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 150, 'en_name' => 'Handkerchiefs', 'ru_name' => 'Носовые платки', 'zh_name' => '手帕', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 151, 'en_name' => 'Glasses and cases', 'ru_name' => 'Очки и футляры', 'zh_name' => '眼鏡和眼鏡盒', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 152, 'en_name' => 'Gloves and mittens', 'ru_name' => 'Перчатки и варежки', 'zh_name' => '手套和連指手套', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 153, 'en_name' => 'Scarves and scarves', 'ru_name' => 'Платки и шарфы', 'zh_name' => '披肩和圍巾', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 154, 'en_name' => 'Religious', 'ru_name' => 'Религиозные', 'zh_name' => '宗教', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 155, 'en_name' => 'Belts and waistbands', 'ru_name' => 'Ремни и пояса', 'zh_name' => '腰帶和腰帶', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 156, 'en_name' => 'Bags and backpacks', 'ru_name' => 'Сумки и рюкзаки', 'zh_name' => '包包和背包', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 157, 'en_name' => 'Watches and straps', 'ru_name' => 'Часы и ремешки', 'zh_name' => '手錶和錶帶', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         ['id' => 158, 'en_name' => 'Luggage cases and luggage protection', 'ru_name' => 'Чемоданы и защита багажа', 'zh_name' => '手提箱和行李保護', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 8],
    //         // parent id = 9
    //         ['id' => 159, 'en_name' => 'Car electronics and navigation', 'ru_name' => 'Автоэлектроника и навигация', 'zh_name' => '汽車電子及導航', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 160, 'en_name' => 'Headsets and headphones', 'ru_name' => 'Гарнитуры и наушники', 'zh_name' => '耳機和耳機', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 161, 'en_name' => "Children's electronics", 'ru_name' => 'Детская электроника', 'zh_name' => '兒童電子產品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 162, 'en_name' => 'Gaming consoles and games', 'ru_name' => 'Игровые консоли и игры', 'zh_name' => '遊戲機和遊戲', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 163, 'en_name' => 'Cables and chargers', 'ru_name' => 'Кабели и зарядные устройства', 'zh_name' => '電纜和充電器', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 164, 'en_name' => 'Music and video', 'ru_name' => 'Музыка и видео', 'zh_name' => '音樂和視頻', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 165, 'en_name' => 'Laptops and computers', 'ru_name' => 'Ноутбуки и компьютеры', 'zh_name' => '筆記型電腦和電腦', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 166, 'en_name' => 'Office equipment', 'ru_name' => 'Офисная техника', 'zh_name' => '辦公室設備', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 167, 'en_name' => 'Entertainment and gadgets', 'ru_name' => 'Развлечения и гаджеты', 'zh_name' => '娛樂和小工具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 168, 'en_name' => 'Network equipment', 'ru_name' => 'Сетевое оборудование', 'zh_name' => '網路裝置', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 169, 'en_name' => 'Security systems', 'ru_name' => 'Системы безопасности', 'zh_name' => '安全系統', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 170, 'en_name' => 'Smartphones and phones', 'ru_name' => 'Смартфоны и телефоны', 'zh_name' => '智慧型手機和電話', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 171, 'en_name' => 'Smart watches and bracelets', 'ru_name' => 'Смарт-часы и браслеты', 'zh_name' => '智慧手錶和手環', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 172, 'en_name' => 'Solar power plants and components', 'ru_name' => 'Солнечные электростанции и комплектующие', 'zh_name' => '太陽能發電廠和組件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 173, 'en_name' => 'TV, Audio, Photo, Video equipment', 'ru_name' => 'ТВ, Аудио, Фото, Видео техника', 'zh_name' => '電視、音訊、照片、視訊設備', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 174, 'en_name' => 'Commercial equipment', 'ru_name' => 'Торговое оборудование', 'zh_name' => '貿易設備', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         ['id' => 175, 'en_name' => 'Smart home', 'ru_name' => 'Умный дом', 'zh_name' => '智慧家庭', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 9],
    //         // parent id = 10
    //         ['id' => 176, 'en_name' => 'Electric transport and accessories', 'ru_name' => 'Электротранспорт и аксессуары', 'zh_name' => '電動車及配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 177, 'en_name' => 'Antistress', 'ru_name' => 'Антистресс', 'zh_name' => '抗壓力', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 178, 'en_name' => 'For kids', 'ru_name' => 'Для малышей', 'zh_name' => '給孩子們', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 179, 'en_name' => 'For the sandbox', 'ru_name' => 'Для песочницы', 'zh_name' => '對於沙箱', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 180, 'en_name' => 'Play complexes', 'ru_name' => 'Игровые комплексы', 'zh_name' => '遊戲綜合體', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 181, 'en_name' => 'Play sets', 'ru_name' => 'Игровые наборы', 'zh_name' => '遊戲套裝', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 182, 'en_name' => 'Toy weapons and accessories', 'ru_name' => 'Игрушечное оружие и аксессуары', 'zh_name' => '玩具武器及配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 183, 'en_name' => 'Toy transport', 'ru_name' => 'Игрушечный транспорт', 'zh_name' => '玩具運輸', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 184, 'en_name' => 'Bath toys', 'ru_name' => 'Игрушки для ванной', 'zh_name' => '沐浴玩具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 185, 'en_name' => 'Interactive', 'ru_name' => 'Интерактивные', 'zh_name' => '互動的', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 186, 'en_name' => 'Kinetic sand', 'ru_name' => 'Кинетический песок', 'zh_name' => '動力砂', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 187, 'en_name' => 'Constructors', 'ru_name' => 'Конструкторы', 'zh_name' => '建構函數', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 188, 'en_name' => 'LEGO constructors', 'ru_name' => 'Конструкторы LEGO', 'zh_name' => '樂高構造者', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 189, 'en_name' => 'Dolls and accessories', 'ru_name' => 'Куклы и аксессуары', 'zh_name' => '玩偶及配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 190, 'en_name' => 'Musical', 'ru_name' => 'Музыкальные', 'zh_name' => '音樂', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 191, 'en_name' => 'Soap bubbles', 'ru_name' => 'Мыльные пузыри', 'zh_name' => '肥皂泡', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 192, 'en_name' => 'Soft toys', 'ru_name' => 'Мягкие игрушки', 'zh_name' => '絨毛玩具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 193, 'en_name' => 'Experiment kits', 'ru_name' => 'Наборы для опытов', 'zh_name' => '實驗套件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 194, 'en_name' => 'Board games', 'ru_name' => 'Настольные игры', 'zh_name' => '棋盤遊戲', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 195, 'en_name' => 'Radio-controlled', 'ru_name' => 'Радиоуправляемые', 'zh_name' => '無線電控制', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 196, 'en_name' => 'Educational toys', 'ru_name' => 'Развивающие игрушки', 'zh_name' => '益智玩具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 197, 'en_name' => 'Assembly models', 'ru_name' => 'Сборные модели', 'zh_name' => '預製模型', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 198, 'en_name' => 'Sports games', 'ru_name' => 'Спортивные игры', 'zh_name' => '體育遊戲', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 199, 'en_name' => 'Role-playing games', 'ru_name' => 'Сюжетно-ролевые игры', 'zh_name' => '角色扮演遊戲', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 200, 'en_name' => 'Creativity and handicrafts', 'ru_name' => 'Творчество и рукоделие', 'zh_name' => '創意與手工藝品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 201, 'en_name' => 'Figures and robots', 'ru_name' => 'Фигурки и роботы', 'zh_name' => '動作人物和機器人', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         ['id' => 202, 'en_name' => 'Storage furniture', 'ru_name' => 'Мебель для хранения', 'zh_name' => '儲物家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 10],
    //         // parent id = 11
    //         ['id' => 203, 'en_name' => 'Frameless furniture', 'ru_name' => 'Бескаркасная мебель', 'zh_name' => '無框家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 204, 'en_name' => "Children's furniture", 'ru_name' => 'Детская мебель', 'zh_name' => '兒童家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 205, 'en_name' => 'Sofas and armchairs', 'ru_name' => 'Диваны и кресла', 'zh_name' => '沙發和扶手椅', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 206, 'en_name' => 'Mattresses', 'ru_name' => 'Матрасы', 'zh_name' => '床墊', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 207, 'en_name' => 'Tables and chairs', 'ru_name' => 'Столы и стулья', 'zh_name' => '桌椅', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 208, 'en_name' => 'Computer and gaming furniture', 'ru_name' => 'Компьютерная и геймерская мебель', 'zh_name' => '電腦和遊戲家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 209, 'en_name' => 'Living room furniture', 'ru_name' => 'Мебель для гостиной', 'zh_name' => '客廳家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 210, 'en_name' => 'Kitchen furniture', 'ru_name' => 'Мебель для кухни', 'zh_name' => '廚房家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 211, 'en_name' => 'Hallway furniture', 'ru_name' => 'Мебель для прихожей', 'zh_name' => '走廊家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 212, 'en_name' => 'Bedroom furniture', 'ru_name' => 'Мебель для спальни', 'zh_name' => '臥室家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 213, 'en_name' => 'Wardrobe furniture', 'ru_name' => 'Гардеробная мебель', 'zh_name' => '衣櫃家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 214, 'en_name' => 'Office furniture', 'ru_name' => 'Офисная мебель', 'zh_name' => '辦公家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 215, 'en_name' => 'Garden furniture', 'ru_name' => 'Садовая мебель', 'zh_name' => '花園家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 216, 'en_name' => 'Commercial furniture', 'ru_name' => 'Торговая мебель', 'zh_name' => '貿易家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 217, 'en_name' => 'Commercial equipment', 'ru_name' => 'Торговое оборудование', 'zh_name' => '貿易設備', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 218, 'en_name' => 'Furniture for beauty salons', 'ru_name' => 'Мебель для салонов красоты', 'zh_name' => '美容院家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 219, 'en_name' => 'Mirrors', 'ru_name' => 'Зеркала', 'zh_name' => '鏡子', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         ['id' => 220, 'en_name' => 'Furniture fittings', 'ru_name' => 'Мебельная фурнитура', 'zh_name' => '家具配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 11],
    //         // parent id = 12
    //         ['id' => 221, 'en_name' => 'Air conditioning equipment', 'ru_name' => 'Климатическая техника', 'zh_name' => '氣候科技', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 12],
    //         ['id' => 222, 'en_name' => 'Beauty and health', 'ru_name' => 'Красота и здоровье', 'zh_name' => '美麗與健康', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 12],
    //         ['id' => 223, 'en_name' => 'Garden equipment', 'ru_name' => 'Садовая техника', 'zh_name' => '庭園設備', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 12],
    //         ['id' => 224, 'en_name' => 'Home appliances', 'ru_name' => 'Техника для дома', 'zh_name' => '家電', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 12],
    //         ['id' => 225, 'en_name' => 'Kitchen appliances', 'ru_name' => 'Техника для кухни', 'zh_name' => '廚房電器', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 12],
    //         ['id' => 226, 'en_name' => 'Large household appliances', 'ru_name' => 'Крупная бытовая техника', 'zh_name' => '大型家電', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 12],
    //         // parent id = 13
    //         ['id' => 227, 'en_name' => 'For cats', 'ru_name' => 'Для кошек', 'zh_name' => '對於貓', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 228, 'en_name' => 'For dogs', 'ru_name' => 'Для собак', 'zh_name' => '對於狗', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 229, 'en_name' => 'For birds', 'ru_name' => 'Для птиц', 'zh_name' => '對於鳥類來說', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 230, 'en_name' => 'For rodents and ferrets', 'ru_name' => 'Для грызунов и хорьков', 'zh_name' => '對於囓齒動物和雪貂', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 231, 'en_name' => 'For horses', 'ru_name' => 'Для лошадей', 'zh_name' => '對於馬', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 232, 'en_name' => 'Aquarium keeping', 'ru_name' => 'Аквариумистика', 'zh_name' => '水族館', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 233, 'en_name' => 'Terrarium keeping', 'ru_name' => 'Террариумистика', 'zh_name' => '地球主義', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 234, 'en_name' => 'Farming', 'ru_name' => 'Фермерство', 'zh_name' => '農業', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 235, 'en_name' => 'Food and treats', 'ru_name' => 'Корм и лакомства', 'zh_name' => '食物和款待', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 236, 'en_name' => 'Feeding accessories', 'ru_name' => 'Аксессуары для кормления', 'zh_name' => '餵食配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 237, 'en_name' => 'Litter trays and fillers', 'ru_name' => 'Лотки и наполнители', 'zh_name' => '托盤和填充物', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 238, 'en_name' => 'Scratching posts and houses', 'ru_name' => 'Когтеточки и домики', 'zh_name' => '抓柱子和房子', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 239, 'en_name' => 'Transportation', 'ru_name' => 'Транспортировка', 'zh_name' => '運輸', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 240, 'en_name' => 'Tack and training', 'ru_name' => 'Амуниция и дрессировка', 'zh_name' => '設備和培訓', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 241, 'en_name' => 'Toys', 'ru_name' => 'Игрушки', 'zh_name' => '玩具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 242, 'en_name' => 'Grooming and care', 'ru_name' => 'Груминг и уход', 'zh_name' => '美容和護理', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 243, 'en_name' => 'Clothing', 'ru_name' => 'Одежда', 'zh_name' => '布', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 244, 'en_name' => 'Veterinary pharmacy', 'ru_name' => 'Ветаптека', 'zh_name' => '獸醫藥房', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         ['id' => 245, 'en_name' => 'Medicines for animals', 'ru_name' => 'Лекарственные препараты для животных', 'zh_name' => '動物藥品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 13],
    //         // parent id = 14
    //         ['id' => 246, 'en_name' => 'Fitness and exercise equipment', 'ru_name' => 'Фитнес и тренажеры', 'zh_name' => '健身及運動器材', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 247, 'en_name' => 'Cycling', 'ru_name' => 'Велоспорт', 'zh_name' => '騎自行車', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 248, 'en_name' => 'Yoga/Pilates', 'ru_name' => 'Йога/Пилатес', 'zh_name' => '瑜珈/普拉提', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 249, 'en_name' => 'Hunting and fishing', 'ru_name' => 'Охота и рыбалка', 'zh_name' => '狩獵和釣魚', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 250, 'en_name' => 'Scooters/Rollerblades/Skateboards', 'ru_name' => 'Самокаты/Ролики/Скейтборды', 'zh_name' => '滑板車/滾輪/滑板', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 251, 'en_name' => 'Tourism/Hiking', 'ru_name' => 'Туризм/Походы', 'zh_name' => '徒步旅行/徒步旅行', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 252, 'en_name' => 'Running/Walking', 'ru_name' => 'Бег/Ходьба', 'zh_name' => '跑步/步行', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 253, 'en_name' => 'Team sports', 'ru_name' => 'Командные виды спорта', 'zh_name' => '團隊運動', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 254, 'en_name' => 'Water sports', 'ru_name' => 'Водные виды спорта', 'zh_name' => '水上運動', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 255, 'en_name' => 'Winter sports', 'ru_name' => 'Зимние виды спорта', 'zh_name' => '冬季運動', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 256, 'en_name' => 'Support and recovery', 'ru_name' => 'Поддержка и восстановление', 'zh_name' => '支持和恢復', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 257, 'en_name' => 'Sports nutrition and cosmetics', 'ru_name' => 'Спортивное питание и косметика', 'zh_name' => '運動營養及化妝品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 258, 'en_name' => 'Badminton/Tennis', 'ru_name' => 'Бадминтон/Теннис', 'zh_name' => '羽毛球/網球', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 259, 'en_name' => 'Billiards/Golf/Darts/Knife throwing', 'ru_name' => 'Бильярд/Гольф/Дартс/Метание ножей', 'zh_name' => '撞球/高爾夫/飛鏢/飛刀投擲', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 260, 'en_name' => 'Martial arts', 'ru_name' => 'Единоборства', 'zh_name' => '武術', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 261, 'en_name' => 'Equestrian sports', 'ru_name' => 'Конный спорт', 'zh_name' => '馬術運動', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 262, 'en_name' => 'Motor sports', 'ru_name' => 'Мотоспорт', 'zh_name' => '賽車運動', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 263, 'en_name' => 'Equipment for passing standards', 'ru_name' => 'Оборудование для сдачи нормативов', 'zh_name' => '通過標準的設備', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 264, 'en_name' => 'Sailing', 'ru_name' => 'Парусный спорт', 'zh_name' => '帆船運動', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 265, 'en_name' => 'Rock climbing/Mountaineering', 'ru_name' => 'Скалолазание/Альпинизм', 'zh_name' => '攀岩/登山', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 266, 'en_name' => 'Airsoft and paintball', 'ru_name' => 'Страйкбол и пейнтбол', 'zh_name' => '氣槍和漆彈', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 267, 'en_name' => 'Dancing/Gymnastics', 'ru_name' => 'Танцы/Гимнастика', 'zh_name' => '舞蹈/體操', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 268, 'en_name' => 'For children', 'ru_name' => 'Для детей', 'zh_name' => '兒童用', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 269, 'en_name' => 'For women', 'ru_name' => 'Для женщин', 'zh_name' => '對女性來說', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 270, 'en_name' => 'For men', 'ru_name' => 'Для мужчин', 'zh_name' => '男士用', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 271, 'en_name' => 'Sports shoes', 'ru_name' => 'Спортивная обувь', 'zh_name' => '運動鞋', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 272, 'en_name' => 'Self-defense products', 'ru_name' => 'Товары для самообороны', 'zh_name' => '自衛產品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         ['id' => 273, 'en_name' => 'Electronics', 'ru_name' => 'Электроника', 'zh_name' => '電子產品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 14],
    //         // parent id = 15
    //         ['id' => 274, 'en_name' => 'Tires and wheel rims', 'ru_name' => 'Шины и диски колесные', 'zh_name' => '輪胎和輪圈', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 275, 'en_name' => 'Spare parts for cars', 'ru_name' => 'Запчасти на легковые автомобили', 'zh_name' => '乘用車備件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 276, 'en_name' => 'Oils and liquids', 'ru_name' => 'Масла и жидкости', 'zh_name' => '油和液體', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 277, 'en_name' => 'Car cosmetics and car chemicals', 'ru_name' => 'Автокосметика и автохимия', 'zh_name' => '汽車化妝品和汽車化學品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 278, 'en_name' => 'Paints and primers', 'ru_name' => 'Краски и грунтовки', 'zh_name' => '油漆和底漆', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 279, 'en_name' => 'Car electronics and navigation', 'ru_name' => 'Автоэлектроника и навигация', 'zh_name' => '汽車電子及導航', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 280, 'en_name' => 'Batteries and related products', 'ru_name' => 'Аккумуляторы и сопутствующие товары', 'zh_name' => '電池及相關產品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 281, 'en_name' => 'Accessories for interior and trunk', 'ru_name' => 'Аксессуары в салон и багажник', 'zh_name' => '內裝和行李箱配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 282, 'en_name' => 'Floor mats', 'ru_name' => 'Коврики', 'zh_name' => '地毯', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 283, 'en_name' => 'External tuning', 'ru_name' => 'Внешний тюнинг', 'zh_name' => '外部調諧', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 284, 'en_name' => 'Other accessories and additional equipment', 'ru_name' => 'Другие аксессуары и доп. оборудование', 'zh_name' => '其他配件和附加件。裝置', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 285, 'en_name' => 'Tools', 'ru_name' => 'Инструменты', 'zh_name' => '工具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 286, 'en_name' => 'High pressure washers and accessories', 'ru_name' => 'Мойки высокого давления и аксессуары', 'zh_name' => '高壓清洗機及配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 287, 'en_name' => 'Motorcycle goods', 'ru_name' => 'Мототовары', 'zh_name' => '摩托車用品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 288, 'en_name' => 'OFFroad', 'ru_name' => 'OFFroad', 'zh_name' => '越野', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 289, 'en_name' => 'Spare parts for power equipment', 'ru_name' => 'Запчасти на силовую технику', 'zh_name' => '電力設備備用零件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         ['id' => 290, 'en_name' => 'Spare parts for boats and motorboats', 'ru_name' => 'Запчасти для лодок и катеров', 'zh_name' => '船舶及船舶備件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 15],
    //         // parent id = 16
    //         ['id' => 291, 'en_name' => 'Fiction', 'ru_name' => 'Художественная литература', 'zh_name' => '小說', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 292, 'en_name' => 'Comics and manga', 'ru_name' => 'Комиксы и манга', 'zh_name' => '漫畫和漫畫', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 293, 'en_name' => 'Books for children', 'ru_name' => 'Книги для детей', 'zh_name' => '兒童書籍', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 294, 'en_name' => 'Child education and development', 'ru_name' => 'Воспитание и развитие ребенка', 'zh_name' => '兒童的養育與發展', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 295, 'en_name' => 'Education', 'ru_name' => 'Образование', 'zh_name' => '教育', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 296, 'en_name' => 'Self-education and development', 'ru_name' => 'Самообразование и развитие', 'zh_name' => '自我教育與發展', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 297, 'en_name' => 'Business and management', 'ru_name' => 'Бизнес и менеджмент', 'zh_name' => '商業與管理', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 298, 'en_name' => 'Hobbies and leisure', 'ru_name' => 'Хобби и досуг', 'zh_name' => '興趣與休閒', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 299, 'en_name' => 'Astrology and esotericism', 'ru_name' => 'Астрология и эзотерика', 'zh_name' => '占星學與深奧學', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 300, 'en_name' => 'Home, garden and vegetable garden', 'ru_name' => 'Дом, сад и огород', 'zh_name' => '家庭、花園和菜園', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 301, 'en_name' => 'Beauty, health and sports', 'ru_name' => 'Красота, здоровье и спорт', 'zh_name' => '美容、健康、運動', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 302, 'en_name' => 'Popular science literature', 'ru_name' => 'Научно-популярная литература', 'zh_name' => '科普文獻', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 303, 'en_name' => 'Internet and technology', 'ru_name' => 'Интернет и технологии', 'zh_name' => '網路與科技', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 304, 'en_name' => 'Literary criticism and journalism', 'ru_name' => 'Литературоведение и публицистика', 'zh_name' => '文學批評與新聞學', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 305, 'en_name' => 'Historical and military literature', 'ru_name' => 'Историческая и военная литература', 'zh_name' => '歷史和軍事文學', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 306, 'en_name' => 'Philosophy', 'ru_name' => 'Философия', 'zh_name' => '哲學', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 307, 'en_name' => 'Religion', 'ru_name' => 'Религия', 'zh_name' => '宗教', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 308, 'en_name' => 'Politics and law', 'ru_name' => 'Политика и право', 'zh_name' => '政治與法律', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 309, 'en_name' => 'Second-hand books', 'ru_name' => 'Букинистика', 'zh_name' => '二手書', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 310, 'en_name' => 'Books in foreign languages', 'ru_name' => 'Книги на иностранных языках', 'zh_name' => '外文書籍', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 311, 'en_name' => 'Posters', 'ru_name' => 'Плакаты', 'zh_name' => '海報', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 312, 'en_name' => 'Calendars', 'ru_name' => 'Календари', 'zh_name' => '日曆', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 313, 'en_name' => 'Collector\'s editions', 'ru_name' => 'Коллекционные издания', 'zh_name' => '收藏版', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 314, 'en_name' => 'Reprint editions', 'ru_name' => 'Репринтные издания', 'zh_name' => '轉載', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 315, 'en_name' => 'Multimedia', 'ru_name' => 'Мультимедиа', 'zh_name' => '多媒體', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 316, 'en_name' => 'Audio books', 'ru_name' => 'Аудиокниги', 'zh_name' => '有聲書', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 317, 'en_name' => 'Digital books', 'ru_name' => 'Цифровые книги', 'zh_name' => '數位圖書', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         ['id' => 318, 'en_name' => 'Digital audio books', 'ru_name' => 'Цифровые аудиокниги', 'zh_name' => '數位有聲書', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 16],
    //         // parent id = 17
    //         ['id' => 319, 'en_name' => 'Rings', 'ru_name' => 'Кольца', 'zh_name' => '戒指', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 320, 'en_name' => 'Earrings', 'ru_name' => 'Серьги', 'zh_name' => '耳環', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 321, 'en_name' => 'Bracelets', 'ru_name' => 'Браслеты', 'zh_name' => '手鍊', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 322, 'en_name' => 'Pendants and charms', 'ru_name' => 'Подвески и шармы', 'zh_name' => '吊墜和吊飾', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 323, 'en_name' => 'Sets', 'ru_name' => 'Комплекты', 'zh_name' => '套件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 324, 'en_name' => 'Necklaces, chains, laces', 'ru_name' => 'Колье, цепи, шнурки', 'zh_name' => '項鍊、鏈條、鞋帶', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 325, 'en_name' => 'Brooches', 'ru_name' => 'Броши', 'zh_name' => '胸針', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 326, 'en_name' => 'Piercing', 'ru_name' => 'Пирсинг', 'zh_name' => '沖孔', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 327, 'en_name' => 'Watches', 'ru_name' => 'Часы', 'zh_name' => '手錶', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 328, 'en_name' => 'Clips, cufflinks, belts', 'ru_name' => 'Зажимы, запонки, ремни', 'zh_name' => '夾子、袖扣、皮帶', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 329, 'en_name' => 'Rosaries', 'ru_name' => 'Четки', 'zh_name' => '珠', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 330, 'en_name' => 'Souvenirs and silverware', 'ru_name' => 'Сувениры и столовое серебро', 'zh_name' => '紀念品和銀器', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 331, 'en_name' => 'Gold jewelry', 'ru_name' => 'Украшения из золота', 'zh_name' => '黃金首飾', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 332, 'en_name' => 'Silver jewelry', 'ru_name' => 'Украшения из серебра', 'zh_name' => '銀飾品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 333, 'en_name' => 'Ceramic jewelry', 'ru_name' => 'Украшения из керамики', 'zh_name' => '陶瓷飾品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         ['id' => 334, 'en_name' => 'Accessories for jewelry', 'ru_name' => 'Аксессуары для украшений', 'zh_name' => '首飾配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 17],
    //         // parent id = 18
    //         ['id' => 335, 'en_name' => 'Paint tinting', 'ru_name' => 'Колеровка краски', 'zh_name' => '油漆調色', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 18],
    //         ['id' => 336, 'en_name' => 'Doors, windows and fittings', 'ru_name' => 'Двери, окна и фурнитура', 'zh_name' => '門、窗及配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 18],
    //         ['id' => 337, 'en_name' => 'Tools and equipment', 'ru_name' => 'Инструменты и оснастка', 'zh_name' => '工具及配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 18],
    //         ['id' => 338, 'en_name' => 'Finishing materials', 'ru_name' => 'Отделочные материалы', 'zh_name' => '飾面材料', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 18],
    //         ['id' => 339, 'en_name' => 'Electrics', 'ru_name' => 'Электрика', 'zh_name' => '電力', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 18],
    //         ['id' => 340, 'en_name' => 'Paints and varnishes', 'ru_name' => 'Лакокрасочные материалы', 'zh_name' => '油漆和清漆材料', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 18],
    //         ['id' => 341, 'en_name' => 'Plumbing, heating and gas supply', 'ru_name' => 'Сантехника, отопление и газоснабжение', 'zh_name' => '管道、暖氣和燃氣供應', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 18],
    //         ['id' => 342, 'en_name' => 'Ventilation', 'ru_name' => 'Вентиляция', 'zh_name' => '通風', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 18],
    //         ['id' => 343, 'en_name' => 'Fasteners', 'ru_name' => 'Крепеж', 'zh_name' => '緊固件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 18],
    //         ['id' => 344, 'en_name' => 'Construction materials', 'ru_name' => 'Стройматериалы', 'zh_name' => '建築材料', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 18],
    //         // parent id = 19
    //         ['id' => 345, 'en_name' => 'Plants, seeds and soils', 'ru_name' => 'Растения, семена и грунты', 'zh_name' => '植物、種子和土壤', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 346, 'en_name' => 'Fertilizers and plant care', 'ru_name' => 'Удобрения и уход за растениями', 'zh_name' => '肥料和植物護理', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 347, 'en_name' => 'Street and garden lighting', 'ru_name' => 'Уличное и садовое освещение', 'zh_name' => '街道和花園照明', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 348, 'en_name' => 'Snow removal tools and reagents', 'ru_name' => 'Инструменты для уборки снега и реагенты', 'zh_name' => '除雪工具和試劑', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 349, 'en_name' => 'Snow removal machines', 'ru_name' => 'Снегоуборочные машины', 'zh_name' => '除雪機', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 350, 'en_name' => 'Greenhouses, hotbeds, covering material', 'ru_name' => 'Теплицы, парники, укрывной материал', 'zh_name' => '溫室、溫室、覆蓋材料', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 351, 'en_name' => 'Bath and sauna products', 'ru_name' => 'Товары для бани и сауны', 'zh_name' => '浴室和桑拿產品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 352, 'en_name' => 'Pots, plant pots and stands', 'ru_name' => 'Горшки, кашпо и подставки для растений', 'zh_name' => '花盆、花盆和植物架', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 353, 'en_name' => 'Grills, barbecues and barbecues', 'ru_name' => 'Грили, мангалы и барбекю', 'zh_name' => '烤架、燒烤爐和燒烤爐', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 354, 'en_name' => 'Garden equipment', 'ru_name' => 'Садовая техника', 'zh_name' => '庭園設備', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 355, 'en_name' => 'Garden tools', 'ru_name' => 'Садовые инструменты', 'zh_name' => '園藝工具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 356, 'en_name' => 'High-pressure washers and accessories', 'ru_name' => 'Мойки высокого давления и аксессуары', 'zh_name' => '高壓清洗機及配件', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 357, 'en_name' => 'Inflatable furniture', 'ru_name' => 'Надувная мебель', 'zh_name' => '充氣家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 358, 'en_name' => 'Products for camping, picnics and recreation', 'ru_name' => 'Товары для кемпинга, пикника и отдыха', 'zh_name' => '露營、野餐和休閒產品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 359, 'en_name' => 'Biotoilets, summer wash basins and showers', 'ru_name' => 'Биотуалеты, дачные умывальники и души', 'zh_name' => '旱廁、鄉村洗臉盆和淋浴', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 360, 'en_name' => 'Garden decor', 'ru_name' => 'Садовый декор', 'zh_name' => '花園裝飾', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 361, 'en_name' => 'Irrigation and water supply', 'ru_name' => 'Полив и водоснабжение', 'zh_name' => '灌溉和供水', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 362, 'en_name' => 'Prefabricated buildings and log cabins', 'ru_name' => 'Готовые строения и срубы', 'zh_name' => '成品建築及木造建築', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 363, 'en_name' => 'Furniture for recreation', 'ru_name' => 'Мебель для отдыха', 'zh_name' => '休閒家具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         ['id' => 364, 'en_name' => 'Protection from insects and rodents', 'ru_name' => 'Защита от насекомых и грызунов', 'zh_name' => '防止昆蟲和囓齒動物的侵害', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 19],
    //         // parent id = 20
    //         ['id' => 365, 'en_name' => 'Swimming pools', 'ru_name' => 'Бассейны', 'zh_name' => '泳池', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 366, 'en_name' => 'Pharmacy', 'ru_name' => 'Аптека', 'zh_name' => '藥局', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 367, 'en_name' => 'Dietary supplements', 'ru_name' => 'БАДы', 'zh_name' => '膳食補充劑', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 368, 'en_name' => 'Disinfection, sterilization and disposal', 'ru_name' => 'Дезинфекция, стерилизация и утилизация', 'zh_name' => '消毒、滅菌和處置', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 369, 'en_name' => 'Ear, throat, nose', 'ru_name' => 'Ухо, горло, нос', 'zh_name' => '耳、喉、鼻', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 370, 'en_name' => 'Complex food supplements', 'ru_name' => 'Комплексные пищевые добавки', 'zh_name' => '複合營養補充品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 371, 'en_name' => 'Contraceptives and lubricants', 'ru_name' => 'Контрацептивы и лубриканты', 'zh_name' => '避孕藥具和潤滑劑', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 372, 'en_name' => 'Specialized nutrition', 'ru_name' => 'Специализированное питание', 'zh_name' => '專業營養', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 373, 'en_name' => 'Protective masks', 'ru_name' => 'Маски защитные', 'zh_name' => '防護口罩', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 374, 'en_name' => 'Medical products', 'ru_name' => 'Медицинские изделия', 'zh_name' => '醫療產品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 375, 'en_name' => 'Medical devices', 'ru_name' => 'Медицинские приборы', 'zh_name' => '醫療器材', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 376, 'en_name' => 'Health improvement', 'ru_name' => 'Оздоровление', 'zh_name' => '健康改善', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 377, 'en_name' => 'Optics', 'ru_name' => 'Оптика', 'zh_name' => '光學', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 378, 'en_name' => 'Orthopedics', 'ru_name' => 'Ортопедия', 'zh_name' => '骨科', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 379, 'en_name' => 'Rehabilitation', 'ru_name' => 'Реабилитация', 'zh_name' => '復健治療', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 380, 'en_name' => 'Syrups and balms', 'ru_name' => 'Сиропы и бальзамы', 'zh_name' => '糖漿和香膏', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         ['id' => 381, 'en_name' => 'Oral care', 'ru_name' => 'Уход за полостью рта', 'zh_name' => '口腔護理', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 20],
    //         // parent id = 21
    //         ['id' => 382, 'en_name' => 'Anatomical models', 'ru_name' => 'Анатомические модели', 'zh_name' => '解剖模型', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 21],
    //         ['id' => 383, 'en_name' => 'Paper products', 'ru_name' => 'Бумажная продукция', 'zh_name' => '紙製品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 21],
    //         ['id' => 384, 'en_name' => 'Maps and globes', 'ru_name' => 'Карты и глобусы', 'zh_name' => '地圖和地球儀', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 21],
    //         ['id' => 385, 'en_name' => 'Office supplies', 'ru_name' => 'Офисные принадлежности', 'zh_name' => '辦公用品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 21],
    //         ['id' => 386, 'en_name' => 'Writing instruments', 'ru_name' => 'Письменные принадлежности', 'zh_name' => '書寫工具', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 21],
    //         ['id' => 387, 'en_name' => 'Drawing and modeling', 'ru_name' => 'Рисование и лепка', 'zh_name' => '繪畫和雕刻', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 21],
    //         ['id' => 388, 'en_name' => 'Counting material', 'ru_name' => 'Счетный материал', 'zh_name' => '計數材料', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 21],
    //         ['id' => 389, 'en_name' => 'Trade supplies', 'ru_name' => 'Торговые принадлежности', 'zh_name' => '貿易用品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 21],
    //         ['id' => 390, 'en_name' => 'Drawing supplies', 'ru_name' => 'Чертежные принадлежности', 'zh_name' => '繪圖用品', 'is_deleted' => 0, 'avatar_id' => 1, 'parent_id' => 21],
    //     ];

    //     foreach ($data as $item) {
    //         $category = new \app\models\Category();
    //         $category->setAttributes($item);
    //         $category->save();
    //     }

    //     Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    //     Yii::$app->response->data = [
    //         'status' => 'success',
    //         'message' => 'Data processed successfully',
    //     ];
    // }
}
