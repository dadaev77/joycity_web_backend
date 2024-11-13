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
     * @OA\Post(
     *     path="/raw/create-chat",
     *     summary="Создать чат",
     *     @OA\Response(response="200", description="Чат успешно создан"),
     *     @OA\Response(response="500", description="Ошибка создания чата")
     * )
     */
    public function actionCreateChat()
    {
        $clientID = 2;
        $managerID = 5;
        $buyerID = 11;
        $fulfilmentID = 20;
        $orderID = 43;

        try {
            $client_manager = ChatConstructorService::createChatOrder(
                Chat::GROUP_CLIENT_MANAGER,
                [$clientID, $managerID],
                $orderID ?? null,
            );

            $client_manager_buyer = ChatConstructorService::createChatOrder(
                Chat::GROUP_CLIENT_BUYER_MANAGER,
                [$clientID, $buyerID, $managerID],
                $orderID ?? null,
            );

            $client_fulfilment_manager = ChatConstructorService::createChatOrder(
                Chat::GROUP_CLIENT_FULFILMENT_MANAGER,
                [$clientID, $fulfilmentID, $managerID],
                $orderID ?? null,
            );

            if ($client_manager->success) {
                echo "Чат [client_manager] успешно создан с Twilio ID: " . $client_manager->data->twilio_id . "\n";
            } else {
                throw new Exception("Ошибка создания чата [client_manager]: " . json_encode($client_manager->errors));
            }

            if ($client_manager_buyer->success) {
                echo "Чат [client_manager_buyer] успешно создан с Twilio ID: " . $client_manager_buyer->data->twilio_id . "\n";
            } else {
                throw new Exception("Ошибка создания чата [client_manager_buyer]: " . json_encode($client_manager_buyer->errors));
            }

            if ($client_fulfilment_manager->success) {
                echo "Чат [client_fulfilment_manager] успешно создан с Twilio ID: " . $client_fulfilment_manager->data->twilio_id . "\n";
            } else {
                throw new Exception("Ошибка создания чата [client_fulfilment_manager]: " . json_encode($client_fulfilment_manager->errors));
            }
        } catch (Exception $e) {
            return [
                'client_manager' => $client_manager->reason,
                'client_manager_buyer' => $client_manager_buyer->reason,
                'client_fulfilment_manager' => $client_fulfilment_manager->reason,
            ];
        }
    }
}
