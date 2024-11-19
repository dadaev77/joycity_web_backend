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
    //         ["en_name" => "Women", "ru_name" => "Женщинам", "zh_name" => "女性", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Footwear", "ru_name" => "Обувь", "zh_name" => "鞋", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Children", "ru_name" => "Детям", "zh_name" => "兒童用", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Men", "ru_name" => "Мужчинам", "zh_name" => "男士用", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Home", "ru_name" => "Дом", "zh_name" => "房子", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Beauty", "ru_name" => "Красота", "zh_name" => "美麗", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Accessories", "ru_name" => "Аксессуары", "zh_name" => "配件", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Electronics", "ru_name" => "Электроника", "zh_name" => "電子產品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Toys", "ru_name" => "Игрушки", "zh_name" => "玩具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Furniture", "ru_name" => "Мебель", "zh_name" => "家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Household appliances", "ru_name" => "Бытовая техника", "zh_name" => "家用電器", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Pet supplies", "ru_name" => "Зоотовары", "zh_name" => "寵物用品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Sports", "ru_name" => "Спорт", "zh_name" => "運動", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Car products", "ru_name" => "Автотовары", "zh_name" => "汽車產品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Books", "ru_name" => "Книги", "zh_name" => "圖書", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Jewelry", "ru_name" => "Ювелирные изделия", "zh_name" => "珠寶", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "For repairs", "ru_name" => "Для ремонта", "zh_name" => "維修用", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Garden and summer cottage", "ru_name" => "Сад и дача", "zh_name" => "花園和別墅", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Health", "ru_name" => "Здоровье", "zh_name" => "健康", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Stationery", "ru_name" => "Канцтовары", "zh_name" => "靜止的", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => null],
    //         ["en_name" => "Blouses and shirts", "ru_name" => "Блузки и рубашки", "zh_name" => "襯衫和襯衫", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Trousers", "ru_name" => "Брюки", "zh_name" => "褲子", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Outerwear", "ru_name" => "Верхняя одежда", "zh_name" => "外套", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Jumpers, turtlenecks and cardigans", "ru_name" => "Джемперы, водолазки и кардиганы", "zh_name" => "毛衣、高領毛衣和開襟衫", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Jeans", "ru_name" => "Джинсы", "zh_name" => "牛仔褲", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Overalls", "ru_name" => "Комбинезоны", "zh_name" => "連身褲", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Suits", "ru_name" => "Костюмы", "zh_name" => "套裝", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Longsleeves", "ru_name" => "Лонгсливы", "zh_name" => "長袖", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Jackets, vests and jackets", "ru_name" => "Пиджаки, жилеты и жакеты", "zh_name" => "西裝外套、背心和夾克", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Dresses and sundresses", "ru_name" => "Платья и сарафаны", "zh_name" => "洋裝和背心裙", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Sweatshirts, sweatshirts and hoodies", "ru_name" => "Толстовки, свитшоты и худи", "zh_name" => "運動衫、運動衫和連帽衫", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Tunics", "ru_name" => "Туники", "zh_name" => "束腰外衣", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "T-shirts and tops", "ru_name" => "Футболки и топы", "zh_name" => "T 恤和上衣", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Robes", "ru_name" => "Халаты", "zh_name" => "長袍", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Shorts", "ru_name" => "Шорты", "zh_name" => "短褲", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Skirts", "ru_name" => "Юбки", "zh_name" => "裙子", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Underwear", "ru_name" => "Белье", "zh_name" => "內衣", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Plus sizes", "ru_name" => "Большие размеры", "zh_name" => "大尺寸", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Expectant mothers", "ru_name" => "Будущие мамы", "zh_name" => "未來的母親", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "For tall", "ru_name" => "Для высоких", "zh_name" => "對於高個子", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "For short", "ru_name" => "Для невысоких", "zh_name" => "對於個子矮的人來說", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Homewear", "ru_name" => "Одежда для дома", "zh_name" => "家居服", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Office", "ru_name" => "Офис", "zh_name" => "辦公室", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Beach fashion", "ru_name" => "Пляжная мода", "zh_name" => "海灘時尚", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Religious", "ru_name" => "Религиозная", "zh_name" => "宗教", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Wedding", "ru_name" => "Свадьба", "zh_name" => "婚禮", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Workwear and PPE", "ru_name" => "Спецодежда и СИЗы", "zh_name" => "工作服和個人防護裝備", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Gifts for women", "ru_name" => "Подарки женщинам", "zh_name" => "給女士的禮物", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 1],
    //         ["en_name" => "Children", "ru_name" => "Детская", "zh_name" => "孩子們的", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 2],
    //         ["en_name" => "For newborns", "ru_name" => "Для новорожденных", "zh_name" => "對於新生兒", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 2],
    //         ["en_name" => "Women's", "ru_name" => "Женская", "zh_name" => "女性的", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 2],
    //         ["en_name" => "Men's", "ru_name" => "Мужская", "zh_name" => "男士", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 2],
    //         ["en_name" => "Safety footwear", "ru_name" => "Спецобувь", "zh_name" => "安全鞋", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 2],
    //         ["en_name" => "Accessories for footwear", "ru_name" => "Аксессуары для обуви", "zh_name" => "鞋材配件", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 2],
    //         ["en_name" => "For girls", "ru_name" => "Для девочек", "zh_name" => "適合女孩", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 3],
    //         ["en_name" => "For boys", "ru_name" => "Для мальчиков", "zh_name" => "適合男孩", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 3],
    //         ["en_name" => "For newborns", "ru_name" => "Для новорожденных", "zh_name" => "對於新生兒", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 3],
    //         ["en_name" => "Children's electronics", "ru_name" => "Детская электроника", "zh_name" => "兒童電子產品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 3],
    //         ["en_name" => "Constructors", "ru_name" => "Конструкторы", "zh_name" => "建構函數", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 3],
    //         ["en_name" => "Children's transport", "ru_name" => "Детский транспорт", "zh_name" => "兒童交通", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 3],
    //         ["en_name" => "Walks and travel", "ru_name" => "Прогулки и путешествия", "zh_name" => "散步和旅行", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 3],
    //         ["en_name" => "Baby food", "ru_name" => "Детское питание", "zh_name" => "嬰兒食品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 3],
    //         ["en_name" => "Religious clothing", "ru_name" => "Религиозная одежда", "zh_name" => "宗教服裝", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 3],
    //         ["en_name" => "Goods for babies", "ru_name" => "Товары для малыша", "zh_name" => "嬰兒用品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 3],
    //         ["en_name" => "Diapers", "ru_name" => "Подгузники", "zh_name" => "尿布", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 3],
    //         ["en_name" => "Gifts for children", "ru_name" => "Подарки детям", "zh_name" => "給孩子的禮物", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 3],
    //         ["en_name" => "Trousers", "ru_name" => "Брюки", "zh_name" => "褲子", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Outerwear", "ru_name" => "Верхняя одежда", "zh_name" => "外套", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Jumpers, turtlenecks and cardigans", "ru_name" => "Джемперы, водолазки и кардиганы", "zh_name" => "毛衣、高領毛衣和開襟衫", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Jeans", "ru_name" => "Джинсы", "zh_name" => "牛仔褲", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Overalls and semi-overalls", "ru_name" => "Комбинезоны и полукомбинезоны", "zh_name" => "工作服和背帶褲", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Suits", "ru_name" => "Костюмы", "zh_name" => "套裝", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Longsleeves", "ru_name" => "Лонгсливы", "zh_name" => "長袖", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "T-shirts", "ru_name" => "Майки", "zh_name" => "米奇", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Jackets, vests and jackets", "ru_name" => "Пиджаки, жилеты и жакеты", "zh_name" => "西裝外套、背心和夾克", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Pajamas", "ru_name" => "Пижамы", "zh_name" => "睡衣", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Shirts", "ru_name" => "Рубашки", "zh_name" => "襯衫", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Sweatshirts, sweatshirts and hoodies", "ru_name" => "Толстовки, свитшоты и худи", "zh_name" => "運動衫、運動衫和連帽衫", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "T-shirts", "ru_name" => "Футболки", "zh_name" => "T恤", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Polo shirts", "ru_name" => "Футболки-поло", "zh_name" => "Polo 衫", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Robes", "ru_name" => "Халаты", "zh_name" => "長袍", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Shorts", "ru_name" => "Шорты", "zh_name" => "短褲", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Underwear", "ru_name" => "Белье", "zh_name" => "內衣", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Plus sizes", "ru_name" => "Большие размеры", "zh_name" => "大尺寸", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "For tall", "ru_name" => "Для высоких", "zh_name" => "對於高個子", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "For short", "ru_name" => "Для невысоких", "zh_name" => "對於個子矮的人來說", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Homewear", "ru_name" => "Одежда для дома", "zh_name" => "家居服", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Office", "ru_name" => "Офис", "zh_name" => "辦公室", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Beach clothing", "ru_name" => "Пляжная одежда", "zh_name" => "沙灘裝", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Religious", "ru_name" => "Религиозная", "zh_name" => "宗教", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Wedding", "ru_name" => "Свадьба", "zh_name" => "婚禮", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Workwear and PPE", "ru_name" => "Спецодежда и СИЗы", "zh_name" => "工作服和個人防護裝備", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Gifts for men", "ru_name" => "Подарки мужчинам", "zh_name" => "給男士的禮物", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 4],
    //         ["en_name" => "Bathroom", "ru_name" => "Ванная", "zh_name" => "浴室", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Kitchen", "ru_name" => "Кухня", "zh_name" => "廚房", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Interior items", "ru_name" => "Предметы интерьера", "zh_name" => "室內裝潢用品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Bedroom", "ru_name" => "Спальня", "zh_name" => "臥室", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Living room", "ru_name" => "Гостиная", "zh_name" => "客廳", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Children's", "ru_name" => "Детская", "zh_name" => "孩子們的", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Leisure and creativity", "ru_name" => "Досуг и творчество", "zh_name" => "休閒與創意", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Everything for the holiday", "ru_name" => "Все для праздника", "zh_name" => "一切都是為了假期", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Mirrors", "ru_name" => "Зеркала", "zh_name" => "鏡子", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Rugs", "ru_name" => "Коврики", "zh_name" => "地毯", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Brackets", "ru_name" => "Кронштейны", "zh_name" => "括號", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Lighting", "ru_name" => "Освещение", "zh_name" => "燈光", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "For smoking", "ru_name" => "Для курения", "zh_name" => "吸煙用", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Outdoor recreation", "ru_name" => "Отдых на природе", "zh_name" => "戶外休閒", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Perfumery for the home", "ru_name" => "Парфюмерия для дома", "zh_name" => "家用香水", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Hallway", "ru_name" => "Прихожая", "zh_name" => "門廳", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Religion, esotericism", "ru_name" => "Религия, эзотерика", "zh_name" => "宗教、深奧", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Souvenir products", "ru_name" => "Сувенирная продукция", "zh_name" => "旅遊紀念品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Household goods", "ru_name" => "Хозяйственные товары", "zh_name" => "家居用品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Storage of things", "ru_name" => "Хранение вещей", "zh_name" => "存放東西", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Flowers, vases and planters", "ru_name" => "Цветы, вазы и кашпо", "zh_name" => "鮮花、花瓶和花盆", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Curtains", "ru_name" => "Шторы", "zh_name" => "窗簾", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 5],
    //         ["en_name" => "Accessories", "ru_name" => "Аксессуары", "zh_name" => "配件", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Hair", "ru_name" => "Волосы", "zh_name" => "頭髮", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Pharmacy cosmetics", "ru_name" => "Аптечная косметика", "zh_name" => "藥房化妝品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Children's decorative cosmetics", "ru_name" => "Детская декоративная косметика", "zh_name" => "兒童裝飾化妝品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "For tanning", "ru_name" => "Для загара", "zh_name" => "用於曬黑", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "For mothers and babies", "ru_name" => "Для мам и малышей", "zh_name" => "給媽媽和寶寶", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Israeli cosmetics", "ru_name" => "Израильская косметика", "zh_name" => "以色列化妝品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Tools for hairdressers", "ru_name" => "Инструменты для парикмахеров", "zh_name" => "美髮師的工具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Korean brands", "ru_name" => "Корейские бренды", "zh_name" => "韓國品牌", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Cosmetic devices and accessories", "ru_name" => "Косметические аппараты и аксессуары", "zh_name" => "美容儀器及配件", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Crimean cosmetics", "ru_name" => "Крымская косметика", "zh_name" => "克里米亞化妝品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Makeup", "ru_name" => "Макияж", "zh_name" => "化妝品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Men's line", "ru_name" => "Мужская линия", "zh_name" => "男系", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Sets for care", "ru_name" => "Наборы для ухода", "zh_name" => "護理套件", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Nails", "ru_name" => "Ногти", "zh_name" => "指甲", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Organic cosmetics", "ru_name" => "Органическая косметика", "zh_name" => "有機化妝品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Perfumery", "ru_name" => "Парфюмерия", "zh_name" => "香料", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Gift sets", "ru_name" => "Подарочные наборы", "zh_name" => "禮品套裝", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Professional cosmetics", "ru_name" => "Профессиональная косметика", "zh_name" => "專業化妝品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Personal hygiene", "ru_name" => "Средства личной гигиены", "zh_name" => "個人護理產品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Oral hygiene", "ru_name" => "Гигиена полости рта", "zh_name" => "口腔衛生", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Skin care", "ru_name" => "Уход за кожей", "zh_name" => "皮膚護理", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Beauty salon furniture", "ru_name" => "Мебель для салонов красоты", "zh_name" => "美容院家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 6],
    //         ["en_name" => "Hair accessories", "ru_name" => "Аксессуары для волос", "zh_name" => "髮飾", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Clothing accessories", "ru_name" => "Аксессуары для одежды", "zh_name" => "服裝輔料", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Costume jewelry", "ru_name" => "Бижутерия", "zh_name" => "珠寶首飾", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Jewelry", "ru_name" => "Ювелирные изделия", "zh_name" => "珠寶", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Fans", "ru_name" => "Веера", "zh_name" => "粉絲", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Ties and bow ties", "ru_name" => "Галстуки и бабочки", "zh_name" => "領帶和領結", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Headwear", "ru_name" => "Головные уборы", "zh_name" => "帽子", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Mirrors", "ru_name" => "Зеркальца", "zh_name" => "鏡子", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Umbrellas", "ru_name" => "Зонты", "zh_name" => "雨傘", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Wallets and credit card holders", "ru_name" => "Кошельки и кредитницы", "zh_name" => "錢包和信用持有人", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Sleep masks", "ru_name" => "Маски для сна", "zh_name" => "睡眠面膜", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Handkerchiefs", "ru_name" => "Носовые платки", "zh_name" => "手帕", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Glasses and cases", "ru_name" => "Очки и футляры", "zh_name" => "眼鏡和眼鏡盒", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Gloves and mittens", "ru_name" => "Перчатки и варежки", "zh_name" => "手套和連指手套", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Scarves and scarves", "ru_name" => "Платки и шарфы", "zh_name" => "披肩和圍巾", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Religious", "ru_name" => "Религиозные", "zh_name" => "宗教", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Belts and waistbands", "ru_name" => "Ремни и пояса", "zh_name" => "腰帶和腰帶", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Bags and backpacks", "ru_name" => "Сумки и рюкзаки", "zh_name" => "包包和背包", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Watches and straps", "ru_name" => "Часы и ремешки", "zh_name" => "手錶和錶帶", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Luggage cases and luggage protection", "ru_name" => "Чемоданы и защита багажа", "zh_name" => "手提箱和行李保護", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 7],
    //         ["en_name" => "Car electronics and navigation", "ru_name" => "Автоэлектроника и навигация", "zh_name" => "汽車電子及導航", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Headsets and headphones", "ru_name" => "Гарнитуры и наушники", "zh_name" => "耳機和耳機", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Children's electronics", "ru_name" => "Детская электроника", "zh_name" => "兒童電子產品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Gaming consoles and games", "ru_name" => "Игровые консоли и игры", "zh_name" => "遊戲機和遊戲", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Cables and chargers", "ru_name" => "Кабели и зарядные устройства", "zh_name" => "電纜和充電器", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Music and video", "ru_name" => "Музыка и видео", "zh_name" => "音樂和視頻", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Laptops and computers", "ru_name" => "Ноутбуки и компьютеры", "zh_name" => "筆記型電腦和電腦", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Office equipment", "ru_name" => "Офисная техника", "zh_name" => "辦公室設備", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Entertainment and gadgets", "ru_name" => "Развлечения и гаджеты", "zh_name" => "娛樂和小工具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Network equipment", "ru_name" => "Сетевое оборудование", "zh_name" => "網路裝置", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Security systems", "ru_name" => "Системы безопасности", "zh_name" => "安全系統", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Smartphones and phones", "ru_name" => "Смартфоны и телефоны", "zh_name" => "智慧型手機和電話", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Smart watches and bracelets", "ru_name" => "Смарт-часы и браслеты", "zh_name" => "智慧手錶和手環", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Solar power plants and components", "ru_name" => "Солнечные электростанции и комплектующие", "zh_name" => "太陽能發電廠和組件", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "TV, Audio, Photo, Video equipment", "ru_name" => "ТВ, Аудио, Фото, Видео техника", "zh_name" => "電視、音訊、照片、視訊設備", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Commercial equipment", "ru_name" => "Торговое оборудование", "zh_name" => "貿易設備", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Smart home", "ru_name" => "Умный дом", "zh_name" => "智慧家庭", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Electric transport and accessories", "ru_name" => "Электротранспорт и аксессуары", "zh_name" => "電動車及配件", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 8],
    //         ["en_name" => "Antistress", "ru_name" => "Антистресс", "zh_name" => "抗壓力", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "For kids", "ru_name" => "Для малышей", "zh_name" => "給孩子們", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "For the sandbox", "ru_name" => "Для песочницы", "zh_name" => "對於沙箱", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Play complexes", "ru_name" => "Игровые комплексы", "zh_name" => "遊戲綜合體", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Play sets", "ru_name" => "Игровые наборы", "zh_name" => "遊戲套裝", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Toy weapons and accessories", "ru_name" => "Игрушечное оружие и аксессуары", "zh_name" => "玩具武器及配件", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Toy transport", "ru_name" => "Игрушечный транспорт", "zh_name" => "玩具運輸", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Bath toys", "ru_name" => "Игрушки для ванной", "zh_name" => "沐浴玩具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Interactive", "ru_name" => "Интерактивные", "zh_name" => "互動的", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Kinetic sand", "ru_name" => "Кинетический песок", "zh_name" => "動力砂", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Constructors", "ru_name" => "Конструкторы", "zh_name" => "建構函數", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "LEGO constructors", "ru_name" => "Конструкторы LEGO", "zh_name" => "樂高構造者", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Dolls and accessories", "ru_name" => "Куклы и аксессуары", "zh_name" => "玩偶及配件", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Musical", "ru_name" => "Музыкальные", "zh_name" => "音樂", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Soap bubbles", "ru_name" => "Мыльные пузыри", "zh_name" => "肥皂泡", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Soft toys", "ru_name" => "Мягкие игрушки", "zh_name" => "絨毛玩具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Experiment kits", "ru_name" => "Наборы для опытов", "zh_name" => "實驗套件", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Board games", "ru_name" => "Настольные игры", "zh_name" => "棋盤遊戲", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Radio-controlled", "ru_name" => "Радиоуправляемые", "zh_name" => "無線電控制", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Educational toys", "ru_name" => "Развивающие игрушки", "zh_name" => "益智玩具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Assembly models", "ru_name" => "Сборные модели", "zh_name" => "預製模型", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Sports games", "ru_name" => "Спортивные игры", "zh_name" => "體育遊戲", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Role-playing games", "ru_name" => "Сюжетно-ролевые игры", "zh_name" => "角色扮演遊戲", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Creativity and handicrafts", "ru_name" => "Творчество и рукоделие", "zh_name" => "創意與手工藝品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Figures and robots", "ru_name" => "Фигурки и роботы", "zh_name" => "動作人物和機器人", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 9],
    //         ["en_name" => "Storage furniture", "ru_name" => "Мебель для хранения", "zh_name" => "儲物家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Frameless furniture", "ru_name" => "Бескаркасная мебель", "zh_name" => "無框家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Children's furniture", "ru_name" => "Детская мебель", "zh_name" => "兒童家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Sofas and armchairs", "ru_name" => "Диваны и кресла", "zh_name" => "沙發和扶手椅", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Mattresses", "ru_name" => "Матрасы", "zh_name" => "床墊", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Tables and chairs", "ru_name" => "Столы и стулья", "zh_name" => "桌椅", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Computer and gaming furniture", "ru_name" => "Компьютерная и геймерская мебель", "zh_name" => "電腦和遊戲家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Living room furniture", "ru_name" => "Мебель для гостиной", "zh_name" => "客廳家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Kitchen furniture", "ru_name" => "Мебель для кухни", "zh_name" => "廚房家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Hallway furniture", "ru_name" => "Мебель для прихожей", "zh_name" => "走廊家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Bedroom furniture", "ru_name" => "Мебель для спальни", "zh_name" => "臥室家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Wardrobe furniture", "ru_name" => "Гардеробная мебель", "zh_name" => "衣櫃家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Office furniture", "ru_name" => "Офисная мебель", "zh_name" => "辦公家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Garden furniture", "ru_name" => "Садовая мебель", "zh_name" => "花園家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Commercial furniture", "ru_name" => "Торговая мебель", "zh_name" => "貿易家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Commercial equipment", "ru_name" => "Торговое оборудование", "zh_name" => "貿易設備", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Furniture for beauty salons", "ru_name" => "Мебель для салонов красоты", "zh_name" => "美容院家具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Mirrors", "ru_name" => "Зеркала", "zh_name" => "鏡子", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Furniture fittings", "ru_name" => "Мебельная фурнитура", "zh_name" => "家具配件", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 10],
    //         ["en_name" => "Air conditioning equipment", "ru_name" => "Климатическая техника", "zh_name" => "氣候科技", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 11],
    //         ["en_name" => "Beauty and health", "ru_name" => "Красота и здоровье", "zh_name" => "美麗與健康", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 11],
    //         ["en_name" => "Garden equipment", "ru_name" => "Садовая техника", "zh_name" => "庭園設備", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 11],
    //         ["en_name" => "Home appliances", "ru_name" => "Техника для дома", "zh_name" => "家電", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 11],
    //         ["en_name" => "Kitchen appliances", "ru_name" => "Техника для кухни", "zh_name" => "廚房電器", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 11],
    //         ["en_name" => "Large household appliances", "ru_name" => "Крупная бытовая техника", "zh_name" => "大型家電", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 11],
    //         ["en_name" => "For cats", "ru_name" => "Для кошек", "zh_name" => "對於貓", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "For dogs", "ru_name" => "Для собак", "zh_name" => "對於狗", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "For birds", "ru_name" => "Для птиц", "zh_name" => "對於鳥類來說", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "For rodents and ferrets", "ru_name" => "Для грызунов и хорьков", "zh_name" => "對於囓齒動物和雪貂", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "For horses", "ru_name" => "Для лошадей", "zh_name" => "對於馬", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Aquarium keeping", "ru_name" => "Аквариумистика", "zh_name" => "水族館", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Terrarium keeping", "ru_name" => "Террариумистика", "zh_name" => "地球主義", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Farming", "ru_name" => "Фермерство", "zh_name" => "農業", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Food and treats", "ru_name" => "Корм и лакомства", "zh_name" => "食物和款待", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Feeding accessories", "ru_name" => "Аксессуары для кормления", "zh_name" => "餵食配件", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Litter trays and fillers", "ru_name" => "Лотки и наполнители", "zh_name" => "托盤和填充物", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Scratching posts and houses", "ru_name" => "Когтеточки и домики", "zh_name" => "抓柱子和房子", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Transportation", "ru_name" => "Транспортировка", "zh_name" => "運輸", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Tack and training", "ru_name" => "Амуниция и дрессировка", "zh_name" => "設備和培訓", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Toys", "ru_name" => "Игрушки", "zh_name" => "玩具", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Grooming and care", "ru_name" => "Груминг и уход", "zh_name" => "美容和護理", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Clothing", "ru_name" => "Одежда", "zh_name" => "布", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Veterinary pharmacy", "ru_name" => "Ветаптека", "zh_name" => "獸醫藥房", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Medicines for animals", "ru_name" => "Лекарственные препараты для животных", "zh_name" => "動物藥品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 12],
    //         ["en_name" => "Fitness and exercise equipment", "ru_name" => "Фитнес и тренажеры", "zh_name" => "健身及運動器材", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Cycling", "ru_name" => "Велоспорт", "zh_name" => "騎自行車", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Yoga/Pilates", "ru_name" => "Йога/Пилатес", "zh_name" => "瑜珈/普拉提", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Hunting and fishing", "ru_name" => "Охота и рыбалка", "zh_name" => "狩獵和釣魚", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Scooters/Rollerblades/Skateboards", "ru_name" => "Самокаты/Ролики/Скейтборды", "zh_name" => "滑板車/滾輪/滑板", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Tourism/Hiking", "ru_name" => "Туризм/Походы", "zh_name" => "徒步旅行/徒步旅行", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Running/Walking", "ru_name" => "Бег/Ходьба", "zh_name" => "跑步/步行", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Team sports", "ru_name" => "Командные виды спорта", "zh_name" => "團隊運動", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Water sports", "ru_name" => "Водные виды спорта", "zh_name" => "水上運動", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Winter sports", "ru_name" => "Зимние виды спорта", "zh_name" => "冬季運動", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Support and recovery", "ru_name" => "Поддержка и восстановление", "zh_name" => "支持和恢復", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Sports nutrition and cosmetics", "ru_name" => "Спортивное питание и косметика", "zh_name" => "運動營養及化妝品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Badminton/Tennis", "ru_name" => "Бадминтон/Теннис", "zh_name" => "羽毛球/網球", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Billiards/Golf/Darts/Knife throwing", "ru_name" => "Бильярд/Гольф/Дартс/Метание ножей", "zh_name" => "撞球/高爾夫/飛鏢/飛刀投擲", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Martial arts", "ru_name" => "Единоборства", "zh_name" => "武術", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Equestrian sports", "ru_name" => "Конный спорт", "zh_name" => "馬術運動", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Motor sports", "ru_name" => "Мотоспорт", "zh_name" => "賽車運動", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Equipment for passing standards", "ru_name" => "Оборудование для сдачи нормативов", "zh_name" => "通過標準的設備", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Sailing", "ru_name" => "Парусный спорт", "zh_name" => "帆船運動", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Rock climbing/Mountaineering", "ru_name" => "Скалолазание/Альпинизм", "zh_name" => "攀岩/登山", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Airsoft and paintball", "ru_name" => "Страйкбол и пейнтбол", "zh_name" => "氣槍和漆彈", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Dancing/Gymnastics", "ru_name" => "Танцы/Гимнастика", "zh_name" => "舞蹈/體操", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "For children", "ru_name" => "Для детей", "zh_name" => "兒童用", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "For women", "ru_name" => "Для женщин", "zh_name" => "對女性來說", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "For men", "ru_name" => "Для мужчин", "zh_name" => "男士用", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Sports shoes", "ru_name" => "Спортивная обувь", "zh_name" => "運動鞋", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Self-defense products", "ru_name" => "Товары для самообороны", "zh_name" => "自衛產品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Electronics", "ru_name" => "Электроника", "zh_name" => "電子產品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 13],
    //         ["en_name" => "Tires and wheel rims", "ru_name" => "Шины и диски колесные", "zh_name" => "輪胎和輪圈", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 14],
    //         ["en_name" => "Spare parts for cars", "ru_name" => "Запчасти на легковые автомобили", "zh_name" => "乘用車備件", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 14],
    //         ["en_name" => "Oils and liquids", "ru_name" => "Масла и жидкости", "zh_name" => "油和液體", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 14],
    //         ["en_name" => "Car cosmetics and car chemicals", "ru_name" => "Автокосметика и автохимия", "zh_name" => "汽車化妝品和汽車化學品", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 14],
    //         ["en_name" => "Paints and primers", "ru_name" => "Краски и грунтовки", "zh_name" => "油漆和底漆", "is_deleted" => 0, "avatar_id" => 1, "parent_id" => 14],
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
