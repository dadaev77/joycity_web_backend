<?php

namespace app\controllers;

use yii\rest\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Yii;
use app\jobs\TestQueueJob;
use app\components\ApiResponse;
use app\components\response\ResponseCodes;

class HealthCheckController extends Controller
{
    private $apiUrl = 'https://api.telegram.org/bot';
    private $azureEndpoint = "https://joyka.openai.azure.com/openai/deployments/";
    private $azureDeploymentId = 'chat_translate_GPT4';
    private $azureApiVersion = '2024-08-01-preview';
    private $azureApiKey = '0c66676b39cc4cf896349a113eb05ff0';
    
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        return $behaviors;
    }

    public function actionChats(): ApiResponse
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->actionLog->info('Starting chat health check', 'health-check');

            $users = [];
            $timestamp = time();
            $userData = [];
            $settingsData = [];

            for ($i = 1; $i <= 3; $i++) {
                $maxRetries = 5;
                $attempt = 0;
                $uuid = null;

                while ($attempt < $maxRetries) {
                    $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
                    $numbers = sprintf('%04d', random_int(0, 9999));
                    $uuid = $letters . '-' . $numbers;

                    if (preg_match('/^[A-Z]{3}-[0-9]{4}$/', $uuid) &&
                        !\app\models\User::find()->where(['uuid' => $uuid])->exists()) {
                        break;
                    }

                    $attempt++;
                    if ($attempt >= $maxRetries) {
                        throw new \Exception('Failed to generate unique UUID after ' . $maxRetries . ' attempts');
                    }
                }

                $email = "test{$i}_{$timestamp}@example.com";

                $userData[] = [
                    'name' => "Test User {$i}",
                    'surname' => "Test Surname {$i}",
                    'email' => $email,
                    'role' => 'buyer',
                    'country' => 'Test Country',
                    'city' => 'Test City',
                    'address' => "Test Address {$i}",
                    'password' => Yii::$app->security->generatePasswordHash("test_password_{$i}"),
                    'personal_id' => md5($timestamp . random_int(1000, 9000)),
                    'phone_number' => '+7' . str_pad($timestamp . $i, 10, '0', STR_PAD_LEFT),
                    'phone_country_code' => '+7',
                    'organization_name' => "Test Organization {$i}",
                    'telegram' => "@testuser{$i}_{$timestamp}",
                    'uuid' => $uuid,
                    'access_token' => Yii::$app->security->generateRandomString(32),
                    'rating' => Yii::$app->params['baseRating'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $settingsData[] = [
                    'currency' => \app\models\UserSettings::CURRENCY_CNY,
                    'application_language' => \app\models\UserSettings::APPLICATION_LANGUAGE_RU,
                    'chat_language' => \app\models\UserSettings::CHAT_LANGUAGE_RU,
                ];
            }

            $userColumns = array_keys($userData[0]);
            Yii::$app->db->createCommand()
                ->batchInsert(\app\models\User::tableName(), $userColumns, $userData)
                ->execute();

            $users = \app\models\User::find()
                ->where(['email' => array_column($userData, 'email')])
                ->all();

            if (count($users) !== 3) {
                throw new \Exception('Failed to create all test users');
            }

            foreach ($users as $index => $user) {
                $settingsData[$index] = [
                    'user_id' => $user->id,
                    'currency' => \app\models\UserSettings::CURRENCY_CNY,
                    'application_language' => \app\models\UserSettings::APPLICATION_LANGUAGE_RU,
                    'chat_language' => \app\models\UserSettings::CHAT_LANGUAGE_RU
                ];
            }

            $settingsColumns = ['user_id', 'currency', 'application_language', 'chat_language'];
            Yii::$app->db->createCommand()
                ->batchInsert(\app\models\UserSettings::tableName(), $settingsColumns, $settingsData)
                ->execute();

            $chat = new \app\models\Chat([
                'type' => 'group',
                'name' => 'Test Chat ' . $timestamp,
                'status' => 'active',
                'user_id' => $users[0]->id,
                'role' => 'owner',
                'metadata' => json_encode([
                    'participants' => array_column($users, 'id'),
                    'created_at' => date('Y-m-d H:i:s')
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$chat->save()) {
                throw new \Exception('Failed to create test chat: ' . json_encode($chat->getErrors()));
            }

            Yii::$app->actionLog->info('Created test chat with ID: ' . $chat->id, 'health-check');

            $messageTypes = [
                ['type' => 'text', 'content' => 'Test message from user', 'file' => null],
                ['type' => 'image', 'content' => null, 'file' => ['path' => '/test/image.jpg', 'type' => 'image/jpeg']],
                ['type' => 'file', 'content' => null, 'file' => ['path' => '/test/document.pdf', 'type' => 'application/pdf']],
                ['type' => 'audio', 'content' => null, 'file' => ['path' => '/test/audio.mp3', 'type' => 'audio/mpeg']],
            ];

            $messages = [];
            foreach ($users as $index => $user) {
                foreach ($messageTypes as $msgType) {
                    $content = $msgType['type'] === 'text'
                        ? $msgType['content'] . ' ' . ($index + 1)
                        : $msgType['content'];

                    $message = \app\services\chats\MessageService::createMessage(
                        $chat->id,
                        $user->id,
                        $msgType['type'],
                        $content,
                        ['read_by' => [$user->id]],
                        null,
                        $msgType['file']
                    );
                    $messages[] = $message;
                }
            }

            Yii::$app->actionLog->info('Created test messages', 'health-check');

            $messageIds = array_column($messages, 'id');
            foreach ($users as $user) {
                \app\models\Message::updateAll(
                    ['metadata' => new \yii\db\Expression('JSON_ARRAY_APPEND(metadata, "$.read_by", :userId)')],
                    ['and',
                        ['id' => $messageIds],
                        ['not like', 'metadata', '"read_by": [*]*' . $user->id . '[*]*']
                    ],
                    ['userId' => $user->id]
                );
            }

            Yii::$app->actionLog->info('Messages marked as read', 'health-check');

            foreach ($users as $user) {
                $userMessage = \app\models\Message::find()
                    ->where(['chat_id' => $chat->id, 'user_id' => $user->id, 'is_deleted' => false])
                    ->one();

                if ($userMessage) {
                    $userMessage->is_deleted = true;
                    $userMessage->deleted_at = date('Y-m-d H:i:s');
                    if (!$userMessage->save()) {
                        throw new \Exception('Failed to delete message for user ' . $user->id);
                    }
                }
            }

            Yii::$app->actionLog->info('Deleted test messages', 'health-check');

            $testData = [
                'chat_id' => $chat->id,
                'users_count' => count($users),
                'messages_count' => count($messages)
            ];

            \app\models\Message::deleteAll(['chat_id' => $chat->id]);
            $chat->delete();
            \app\models\UserSettings::deleteAll(['user_id' => array_column($users, 'id')]);
            \app\models\User::deleteAll(['id' => array_column($users, 'id')]);

            $transaction->commit();
            Yii::$app->actionLog->info('Chat health check completed successfully', 'health-check');

            $response = new ApiResponse();
            $response->format = ApiResponse::FORMAT_JSON;
            $response->data = ApiResponse::info([
                'status' => 'ok',
                'message' => 'Chat functionality is working properly',
                'test_data' => $testData
            ]);
            return $response;
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->actionLog->error('Chat health check failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'health-check');
            
            $response = new ApiResponse();
            $response->format = ApiResponse::FORMAT_JSON;
            $response->data = ApiResponse::codeErrors(
                ResponseCodes::getStatic()->INTERNAL_ERROR,
                [
                    'message' => 'Chat functionality is not working: ' . $e->getMessage(),
                    'trace' => YII_DEBUG ? $e->getTraceAsString() : null
                ],
                500
            );
            $response->statusCode = 500;
            return $response;
        }
    }

    public function actionQueue()
    {
        try {
            Yii::$app->actionLog->info('Начало проверки очереди', 'health-check');
            
            $tableName = Yii::$app->db->schema->getRawTableName('{{%queue}}');
            Yii::$app->actionLog->info('Проверяем таблицу: ' . $tableName, 'health-check');
            
            $tableExists = Yii::$app->db->createCommand("SHOW TABLES LIKE '{$tableName}'")->queryScalar();
            if (!$tableExists) {
                throw new \Exception('Таблица очереди не существует');
            }
            Yii::$app->actionLog->info('Таблица очереди существует', 'health-check');
            
            $testData = 'test_' . time();
            Yii::$app->actionLog->info('Создаем тестовую задачу с данными: ' . $testData, 'health-check');
            
            $job = new TestQueueJob([
                'data' => $testData
            ]);
            
            $jobId = Yii::$app->queue->push($job);
            Yii::$app->actionLog->info('ID созданной задачи: ' . ($jobId ?: 'null'), 'health-check');

            if (!$jobId) {
                throw new \Exception('Не удалось создать задачу в очереди');
            }

            $jobRecord = (new \yii\db\Query())
                ->select(['id', 'channel', 'pushed_at', 'reserved_at', 'attempt', 'done_at'])
                ->from($tableName)
                ->where(['id' => $jobId])
                ->one();
                
            if (!$jobRecord) {
                throw new \Exception('Задача не найдена в базе данных после создания');
            }
            Yii::$app->actionLog->info('Задача успешно создана в БД', 'health-check');

            Yii::$app->actionLog->info('Начинаем выполнение задачи', 'health-check');
            
            Yii::$app->db->createCommand()
                ->update($tableName, 
                    ['reserved_at' => time(), 'attempt' => 1],
                    ['id' => $jobId]
                )->execute();
            
            $result = $job->execute(Yii::$app->queue);
            Yii::$app->actionLog->info('Результат выполнения задачи: ' . ($result ? 'true' : 'false'), 'health-check');
            
            if (!$result) {
                throw new \Exception('Не удалось выполнить задачу');
            }

            Yii::$app->db->createCommand()
                ->update($tableName, 
                    ['done_at' => time()],
                    ['id' => $jobId]
                )->execute();

            $jobRecord = (new \yii\db\Query())
                ->select(['id', 'channel', 'pushed_at', 'reserved_at', 'attempt', 'done_at'])
                ->from($tableName)
                ->where(['id' => $jobId])
                ->one();
                
            if (!$jobRecord['done_at']) {
                throw new \Exception('Задача не отмечена как выполненная');
            }

            Yii::$app->actionLog->info('Очищаем тестовые задачи', 'health-check');
            $deleted = Yii::$app->db->createCommand()
                ->delete($tableName, ['id' => $jobId])
                ->execute();
            Yii::$app->actionLog->info('Удалено тестовых задач: ' . $deleted, 'health-check');

            Yii::$app->actionLog->info('Проверка очереди успешно завершена', 'health-check');
            return $this->asJson([
                'status' => 'ok',
                'message' => 'Queue is working properly',
                'job_id' => $jobId,
                'job_data' => $jobRecord
            ])->setStatusCode(200);

        } catch (\Exception $e) {
            Yii::$app->actionLog->error('Queue health check failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'health-check');
            return $this->asJson([
                'status' => 'error',
                'message' => 'Queue is not working: ' . $e->getMessage(),
                'trace' => YII_DEBUG ? $e->getTraceAsString() : null
            ])->setStatusCode(500);
        }
    }

    public function actionRates()
    {
        return $this->asJson('Rates is ok')->setStatusCode(200);
    }

    public function actionAzure()
    {
        try {
            $client = new Client();
            $url = $this->azureEndpoint . $this->azureDeploymentId . "/chat/completions?api-version=" . $this->azureApiVersion;
            
            $data = [
                "messages" => [
                    [
                        "role" => "system",
                        "content" => "Imagine that you are a professional linguist and translator."
                    ],
                    [
                        "role" => "user",
                        "content" => "Translate the following text into 3 languages: English, Russian, Chinese.\n" .
                            "- Do NOT swap languages: \n" .
                            "- 'ru' must contain only Russian translations.\n" .
                            "- 'en' must contain only English translations.\n" .
                            "- 'zh' must contain only Chinese translations.\n" .
                            "- Maintain punctuation, spacing, and capitalization as in the original text.\n" .
                            "- Return only the JSON object: {\"ru\": \"translation in Russian\", \"en\": \"translation in English\", \"zh\": \"translation in Chinese\"}.\n" .
                            "Original text is: Ἡ μὲν ῥίζα τῆς παιδείας πικρά ἐστι, ὁ δὲ καρπὸς γλυκύς"
                    ]
                ]
            ];

            $headers = [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . $this->azureApiKey,
                "api-key" => $this->azureApiKey
            ];

            try {
                $response = $client->request('POST', $url, [
                    'headers' => $headers,
                    'json' => $data
                ]);

                $result = json_decode($response->getBody()->getContents(), true);
                
                if ($response->getStatusCode() === 200 && 
                    isset($result['choices']) && 
                    !empty($result['choices']) &&
                    isset($result['choices'][0]['message']['content'])) {
                    
                    $translations = json_decode($result['choices'][0]['message']['content'], true);
                    
                    if (is_array($translations) && 
                        isset($translations['ru']) && 
                        isset($translations['en']) && 
                        isset($translations['zh'])) {
                        
                        Yii::$app->actionLog->info('Azure translation test successful', 'health-check');
                        return $this->asJson([
                            'status' => 'Azure is ok',
                            'translations' => $translations
                        ])->setStatusCode(200);
                    } else {
                        Yii::$app->actionLog->error('Azure health check failed: Invalid translation structure', 'health-check');
                        return $this->asJson('Azure is not ok - Invalid translation structure')->setStatusCode(500);
                    }
                } else {
                    Yii::$app->actionLog->error('Azure health check failed: Invalid response structure', 'health-check');
                    return $this->asJson('Azure is not ok - Invalid response')->setStatusCode(500);
                }
            } catch (GuzzleException $e) {
                Yii::$app->actionLog->error('Azure health check failed: ' . $e->getMessage(), 'health-check');
                return $this->asJson('Azure is not ok - Connection error')->setStatusCode(503);
            }
        } catch (\Exception $e) {
            Yii::$app->actionLog->error('Azure health check failed: ' . $e->getMessage(), 'health-check');
            return $this->asJson('Azure is not ok - General error')->setStatusCode(503);
        }
    }

    public function actionWebSockets()
    {
        return $this->asJson('WebSockets is ok')->setStatusCode(200);
    }

    public function actionTelegram()
    {
        try {
            $botTokens = [
                'prod' => $_ENV['APP_LOG_BOT_TOKEN_PROD'] ?? null,
                'stage' => $_ENV['APP_LOG_BOT_TOKEN_STAGE'] ?? null
            ];

            if (!$botTokens['prod'] && !$botTokens['stage']) {
                return $this->asJson('Telegram is not configured')->setStatusCode(503);
            }
            
            $hasError = false;

            foreach ($botTokens as $env => $token) {
                if (!$token) {
                    continue;
                }

                try {
                    $client = new Client();
                    $url = $this->apiUrl . $token . '/getMe';
                    $response = $client->request('GET', $url);
                    $result = json_decode($response->getBody()->getContents(), true);

                    if ($result['ok'] !== true) {
                        $hasError = true;
                    }
                } catch (\Exception $e) {
                    $hasError = true;
                }
            }

            return $this->asJson('Telegram is ' . ($hasError ? 'not ok' : 'ok'))->setStatusCode($hasError ? 500 : 200);

        } catch (\Exception $e) {
            return $this->asJson('Telegram is not ok')->setStatusCode(503);
        }
    }
}
