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

    public function actionFb()
    {
        $user_id = Yii::$app->request->post('user_id');
        $message = Yii::$app->request->post('message');

        return \app\services\push\PushService::sendPushNotification($user_id, $message);
    }

    public function actionJob()
    {
        $text = Yii::$app->request->post('message');

        $api_key = '0c66676b39cc4cf896349a113eb05ff0';
        $endpoint = "https://joyka.openai.azure.com/openai/deployments/";
        $deployment_id = 'chat_translate_GPT4';
        $api_version = '2024-08-01-preview';
        $url = $endpoint . $deployment_id . "/chat/completions?api-version=" . $api_version;

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $api_key,
            "api-key: " . $api_key
        ];
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $result = json_decode($response, true);
            return $result;
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
    public function actionBuyersTokens()
    {

        $buyers = User::find()->where(['role' => 'buyer'])->all();
        foreach ($buyers as $buyer) {
            $buyer->pushTokens;
            echo "\n" . "\033[38;5;214m" . "[PT Count] " . $buyer->id . ": " . count($buyer->pushTokens) . "\033[0m";
            foreach ($buyer->pushTokens as $pushToken) {
                echo "\n" . "\033[31m" . "--[PT:{$buyer->id}] " . $pushToken->push_token . "\033[0m";
            }
        }
    }
}
