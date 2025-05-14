<?php

namespace app\controllers;

use yii\rest\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Yii;
use app\jobs\TestQueueJob;

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

    public function actionChats()
    {
        return $this->asJson('Chats is ok')->setStatusCode(200);
    }

    public function actionQueue()
    {
        try {
            Yii::info('Начало проверки очереди', 'health-check');
            
            
            $tableName = Yii::$app->db->schema->getRawTableName('{{%queue}}');
            Yii::info('Проверяем таблицу: ' . $tableName, 'health-check');
            
            $tableExists = Yii::$app->db->createCommand("SHOW TABLES LIKE '{$tableName}'")->queryScalar();
            if (!$tableExists) {
                throw new \Exception('Таблица очереди не существует');
            }
            Yii::info('Таблица очереди существует', 'health-check');
            
            
            $testData = 'test_' . time();
            Yii::info('Создаем тестовую задачу с данными: ' . $testData, 'health-check');
            
            $job = new TestQueueJob([
                'data' => $testData
            ]);
            
            $jobId = Yii::$app->queue->push($job);
            Yii::info('ID созданной задачи: ' . ($jobId ?: 'null'), 'health-check');

            if (!$jobId) {
                throw new \Exception('Не удалось создать задачу в очереди');
            }

            // Шаг 3: Проверяем, что задача создалась в БД
            $jobRecord = (new \yii\db\Query())
                ->select(['id', 'channel', 'pushed_at', 'reserved_at', 'attempt', 'done_at'])
                ->from($tableName)
                ->where(['id' => $jobId])
                ->one();
                
            if (!$jobRecord) {
                throw new \Exception('Задача не найдена в базе данных после создания');
            }
            Yii::info('Задача успешно создана в БД', 'health-check');

            // Шаг 4: Выполняем задачу
            Yii::info('Начинаем выполнение задачи', 'health-check');
            
            // Обновляем статус задачи как "в процессе"
            Yii::$app->db->createCommand()
                ->update($tableName, 
                    ['reserved_at' => time(), 'attempt' => 1],
                    ['id' => $jobId]
                )->execute();
            
            // Выполняем задачу
            $result = $job->execute(Yii::$app->queue);
            Yii::info('Результат выполнения задачи: ' . ($result ? 'true' : 'false'), 'health-check');
            
            if (!$result) {
                throw new \Exception('Не удалось выполнить задачу');
            }

            // Отмечаем задачу как выполненную
            Yii::$app->db->createCommand()
                ->update($tableName, 
                    ['done_at' => time()],
                    ['id' => $jobId]
                )->execute();

            // Шаг 5: Проверяем статус выполнения
            $jobRecord = (new \yii\db\Query())
                ->select(['id', 'channel', 'pushed_at', 'reserved_at', 'attempt', 'done_at'])
                ->from($tableName)
                ->where(['id' => $jobId])
                ->one();
                
            if (!$jobRecord['done_at']) {
                throw new \Exception('Задача не отмечена как выполненная');
            }

            // Шаг 6: Очищаем тестовые задачи
            Yii::info('Очищаем тестовые задачи', 'health-check');
            $deleted = Yii::$app->db->createCommand()
                ->delete($tableName, ['id' => $jobId])
                ->execute();
            Yii::info('Удалено тестовых задач: ' . $deleted, 'health-check');

            Yii::info('Проверка очереди успешно завершена', 'health-check');
            return $this->asJson([
                'status' => 'ok',
                'message' => 'Queue is working properly',
                'job_id' => $jobId,
                'job_data' => $jobRecord
            ])->setStatusCode(200);

        } catch (\Exception $e) {
            Yii::error('Queue health check failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'health-check');
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
                        
                        Yii::info('Azure translation test successful', 'health-check');
                        return $this->asJson([
                            'status' => 'Azure is ok',
                            'translations' => $translations
                        ])->setStatusCode(200);
                    } else {
                        Yii::error('Azure health check failed: Invalid translation structure', 'health-check');
                        return $this->asJson('Azure is not ok - Invalid translation structure')->setStatusCode(500);
                    }
                } else {
                    Yii::error('Azure health check failed: Invalid response structure', 'health-check');
                    return $this->asJson('Azure is not ok - Invalid response')->setStatusCode(500);
                }
            } catch (GuzzleException $e) {
                Yii::error('Azure health check failed: ' . $e->getMessage(), 'health-check');
                return $this->asJson('Azure is not ok - Connection error')->setStatusCode(503);
            }
        } catch (\Exception $e) {
            Yii::error('Azure health check failed: ' . $e->getMessage(), 'health-check');
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
