<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\User;
use app\models\Product;
use app\models\Order as OrderModel;

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

    public function actionAuth()
    {
        Yii::$app->response->format = Response::FORMAT_HTML;
        return $this->renderPartial('auth');
    }

    public function actionLogin()
    {
        Yii::$app->response->format = Response::FORMAT_HTML;
        $request = Yii::$app->request->post();
        $email = $request['email'];
        $password = $request['password'];

        $user = User::find()->where(['email' => $email])->one();
        if (!$user) {
            return $this->renderPartial('auth', ['error' => 'Пользователь не найден']);
        }

        if (!Yii::$app->security->validatePassword($password, $user->password)) {
            return $this->renderPartial('auth', ['error' => 'Неверный пароль']);
        }

        $_COOKIE['auth'] = hash('sha256', $user->password);
        setcookie('auth', $_COOKIE['auth'], time() + 3600, '/');
        if ($user->role == 'admin') {
            header('Location: /raw/log');
        } else {
            return $this->renderPartial('auth', ['error' => 'Неверная роль пользователя для входа в систему']);
        }
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
        if (!isset($_COOKIE['auth'])) {
            header('Location: /raw/auth');
            exit;
        }
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

        // Получаем пользователей
        $clients = User::find()->where(['role' => 'client'])->orderBy(['id' => SORT_DESC])->all();
        $managers = User::find()->where(['role' => 'manager'])->orderBy(['id' => SORT_DESC])->all();
        $fulfillment = User::find()->where(['role' => 'fulfillment'])->orderBy(['id' => SORT_DESC])->all();
        $buyers = User::find()->where(['role' => 'buyer'])->orderBy(['id' => SORT_DESC])->all();

        // Получаем продукты и заказы
        $products = Product::find()
            ->select('*')
            ->orderBy(['id' => SORT_DESC])
            ->limit(100)
            ->all();
        $orders = OrderModel::find()
            ->select('*')
            ->orderBy(['id' => SORT_DESC])
            ->limit(100)
            ->all();

        // Подготавливаем данные для заказов
        $ordersData = [];
        foreach ($orders as $order) {
            $manager = User::findOne($order->manager_id);
            $buyer = User::findOne($order->buyer_id);
            
            $ordersData[] = [
                'order' => $order,
                'manager' => $manager ? [
                    'name' => $manager->name,
                    'surname' => $manager->surname
                ] : null,
                'buyer' => $buyer ? [
                    'name' => $buyer->name,
                    'surname' => $buyer->surname
                ] : null
            ];
        }

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
            'orders' => $ordersData,
            'attachments' => $attachments,
            'actionLogs' => $actionLogs,
            'profilingLogs' => $profilingLogs,
        ], false);
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
     *     path="/raw/order-details",
     *     summary="Получить детальную информацию о заявке",
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Информация о заявке успешно получена"),
     *     @OA\Response(response="404", description="Заявка не найдена")
     * )
     */
    public function actionOrderDetails()
    {
        if (!isset($_COOKIE['auth'])) {
            return $this->asJson([
                'error' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $orderId = Yii::$app->request->get('id');
        $order = OrderModel::findOne($orderId);

        if (!$order) {
            return $this->asJson([
                'error' => 'Order not found'
            ])->setStatusCode(404);
        }

        // Получаем информацию о пользователях
        $createdBy = User::findOne($order->created_by);
        $manager = User::findOne($order->manager_id);
        $buyer = User::findOne($order->buyer_id);

        // Получаем предложения по заявке
        $buyerOffers = $order->buyerOffers;
        $fulfillmentOffer = $order->fulfillmentOffer;
        
        $formattedProposals = [];
        
        // Форматируем предложения покупателей
        foreach ($buyerOffers as $offer) {
            $sender = User::findOne($offer->buyer_id);
            $formattedProposals[] = [
                'id' => $offer->id,
                'sender_name' => $sender ? $sender->name . ' ' . $sender->surname : 'Неизвестный отправитель',
                'price' => number_format((float)$offer->price_product, 2, '.', ' '),
                'status' => $offer->status,
                'created_at' => $offer->created_at
            ];
        }
        
        // Добавляем предложение фулфилмента, если оно есть
        if ($fulfillmentOffer) {
            $sender = User::findOne($fulfillmentOffer->fulfillment_id);
            $formattedProposals[] = [
                'id' => $fulfillmentOffer->id,
                'sender_name' => $sender ? $sender->name . ' ' . $sender->surname : 'Неизвестный отправитель',
                'price' => number_format((float)$fulfillmentOffer->overall_price, 2, '.', ' '),
                'status' => $fulfillmentOffer->status,
                'created_at' => $fulfillmentOffer->created_at
            ];
        }

        return $this->asJson([
            'id' => $order->id,
            'status' => $order->status,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'product_name_ru' => $order->product_name_ru,
            'total_quantity' => $order->total_quantity,
            'price_product' => number_format((float)$order->price_product, 2, '.', ' '),
            'price_delivery' => number_format((float)$order->price_delivery, 2, '.', ' '),
            'created_by_name' => $createdBy ? $createdBy->name . ' ' . $createdBy->surname : 'Неизвестный создатель',
            'manager_name' => $manager ? $manager->name . ' ' . $manager->surname : 'Не назначен',
            'buyer_name' => $buyer ? $buyer->name . ' ' . $buyer->surname : 'Не назначен',
            'proposals' => $formattedProposals
        ]);
    }
}
