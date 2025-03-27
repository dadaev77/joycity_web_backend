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

        $original_message = Yii::$app->request->post('message');

        $api_key = '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';
        $endpoint = "https://api.openai.com/v1/chat/completions";

        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer {$api_key}"
        ];

        $instruction = "Imagine that you are a professional linguist and translator.";

        $prompt = "Translate the following text into 3 languages: English, Russian, Chinese.
            - Do NOT swap languages: 
            - 'ru' must contain only Russian translations.
            - 'en' must contain only English translations.
            - 'zh' must contain only Chinese translations.  
            - Maintain punctuation, spacing, and capitalization as in the original text. Do not add or remove any text.
            - Provide only literal translations, avoiding interpretations or additional commentary.  
            - Do not perform any mathematical operations.  
            - Use transliteration for slang terms or abbreviations.  
            - If a word contains an error, suggest a similar word in meaning or transliterate it.  
            - If unsure of a translation, default to transliteration.  
            - Do not translate Russian words into English or English words into Russian unless specified.  
            - Return only the JSON object with no surrounding text or formatting.  
            - Clear all previous conversation context after completing the translation. 
            - For example, if the word \"товор\" contains an error, replace it with a similar word and transliterate it for English and Chinese.
            - Only the json object, without phrases and notes.
            - A word in the middle of a text with a capital letter is not always a name of something. Understand from the context whether it is a name or not. If not, translate it as a regular word. If it is a name, leave it unchanged.
            - Translate the entire text from beginning to end. Do not shorten it, even if repetitions are used. Your task is simply to translate from one language to another.
            - But do not translate brand names (e.g., Apple, Sony, Samsung, etc).
            - Also, adapt the translation to natural language structures while preserving the overall meaning of the phrase.
            - Structure the response as a JSON object:
            {{ \"ru\": \"translation in Russian\", \"en\": \"translation in English\", \"zh\": \"translation in Chinese\" }}, and nothing else.
            Original text is: " . $original_message;


        $data = [
            "model" => 'gpt-4o-mini-2024-07-18',
            "messages" => [
                ["role" => "system", "content" => $instruction],
                ["role" => "user", "content" => $prompt]
            ]
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status === 200) {
            $response_data = json_decode($response, true);
            return $response_data['choices'][0]['message']['content'];
        } else {
            error_log("Ошибка запроса к chatGPT (chatgpt_request): " . $response);
            return "Ошибка: HTTP $http_status";
        }
    }
}
