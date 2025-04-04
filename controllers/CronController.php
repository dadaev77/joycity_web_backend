<?php

namespace app\controllers;

use Yii;
use app\models\OrderDistribution;
use app\models\Rate;
use yii\web\Controller;
use app\services\ExchangeRateService;
use app\models\Heartbeat;

class CronController extends Controller
{
    private $services = [
        'rates' => 'Курсы валют',
        'distribution' => 'Распределение заказов байеров',
        'cleanup-guest-accounts' => 'Очистка гостевых аккаунтов: Покупатель-демо и Клиент-демо',
    ];

    public function init()
    {
        parent::init();
    }

    /**
     * @OA\Get(
     *     path="/cron/create",
     *     summary="Создать задачу cron",
     *     @OA\Parameter(name="taskID", in="query", required=true, description="ID задачи"),
     *     @OA\Parameter(name="schedule", in="query", required=false, description="Расписание задачи (например, '* * * * *')"),
     *     @OA\Response(response="200", description="Задача cron создана"),
     *     @OA\Response(response="400", description="Неверный ID задачи или задача уже существует")
     * )
     */

    public static function actionCreate($taskID = null, $schedule = '* * * * *')
    {
        $task = OrderDistribution::find()->where(['id' => $taskID])->one();
        if (!$task) {
            Yii::$app->actionLog->error('Несуществующий ID задачи: ' . $taskID);
            return false;
        }
        $existingTasks = shell_exec("crontab -l | grep 'taskID={$taskID}'");
        if (!empty($existingTasks)) {
            return false;
        }

        $command = "$schedule curl -X GET \"" . $_ENV['APP_URL'] . "/cron/distribution?taskID={$taskID}\"";
        try {
            exec("crontab -l | { cat; echo '$command'; } | crontab - ", $output, $returnVar);

            if ($returnVar === 0) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Yii::$app->actionLog->error('Ошибка создания задачи cron: ' . $taskID . ' - ' . $e->getMessage());
            Yii::$app->telegramLog->send(
                'error',
                'Ошибка создания задачи cron: ' . $taskID . ' - ' . $e->getMessage()
            );
            return false;
        }
    }

    /**
     * @OA\Get(
     *     path="/cron/distribution",
     *     summary="Распределить заказы",
     *     @OA\Parameter(name="taskID", in="query", required=true, description="ID задачи"),
     *     @OA\Response(response="200", description="Задача распределения выполнена"),
     *     @OA\Response(response="404", description="Задача не найдена")
     * )
     */
    public function actionDistribution(string $taskID = null)
    {
        $actualTask = OrderDistribution::find()->where(['id' => $taskID])->one();

        if (
            !$actualTask ||
            $actualTask->status !== OrderDistribution::STATUS_IN_WORK
        ) {
            $command = "crontab -l | grep -v 'taskID={$taskID}' | crontab -";
            exec($command);
            Yii::$app->actionLog->error('Задача не найдена или не в работе: ' . $taskID);
            return;
        }
        $buyers = explode(',', $actualTask->buyer_ids_list);
        $currentBuyer = $actualTask->current_buyer_id;
        $nextBuyer = $this->getNextBuyer($buyers, $currentBuyer);
        $actualTask->current_buyer_id = $nextBuyer;
        if (!$actualTask->save()) {
            Yii::$app->actionLog->error('Ошибка сохранения задачи: ' . $taskID);
            return;
        }
        Yii::$app->actionLog->success('Задача распределена успешно: ' . $taskID);
    }

    private function getNextBuyer(array $buyers, int $currentBuyer): int
    {
        $index = array_search($currentBuyer, $buyers);
        if ($index === false || $index === count($buyers) - 1) {
            return $buyers[0];
        }
        return $buyers[$index + 1];
    }

    /**
     * @OA\Get(
     *     path="/cron/update-rates",
     *     summary="Обновить курсы валют",
     *     @OA\Response(response="200", description="Курсы обновлены"),
     *     @OA\Response(response="500", description="Ошибка сохранения курсов")
     * )
     */
    public function actionUpdateRates()
    {
        $rates = ExchangeRateService::getRate(['cny', 'usd']);

        if (!empty($rates['data'])) {
            $rate = new \app\models\Rate();
            $rate->RUB = 1;
            $rate->USD = round($rates['data']['USD'] * 1.05, 4);
            $rate->CNY = round($rates['data']['CNY'] * 1.07, 4);

            if ($rate->save()) {
                Yii::$app->heartbeat->addHeartbeat('rates', 'success');
                Yii::$app->telegramLog->send(
                    'success',
                    [
                        'Курсы обновлены:',
                        'USD - ' . $rates['data']['USD'] . ', CNY - ' . $rates['data']['CNY'],
                        'Курсы + проценты: USD + 2% - ' . $rate->USD . ', CNY + 5% - ' . $rate->CNY,
                    ],
                    'rates'
                );
                return ['status' => 'success', 'message' => 'Курсы обновлены'];
            } else {
                Yii::$app->telegramLog->send(
                    'error',
                    'Ошибка сохранения курсов'
                , 'rates');
                return ['status' => 'error', 'message' => 'Ошибка сохранения курсов'];
            }
        }

        Yii::$app->telegramLog->send(
            'error',
            'Нет данных для обновления курсов'
        , 'rates');
        return ['status' => 'error', 'message' => 'Нет данных для обновления курсов'];
    }

    /**
     * @OA\Get(
     *     path="/cron/clear-rates",
     *     summary="Очистить старые курсы",
     *     @OA\Response(response="200", description="Курсы очищены"),
     *     @OA\Response(response="500", description="Ошибка очистки курсов")
     * )
     */
    public function actionClearRates()
    {
        $rates = Rate::find()->orderBy(['id' => SORT_DESC])->all();
        if (count($rates) > 1) {
            foreach (array_slice($rates, 1) as $rate) {
                $rate->delete();
            }
            Yii::$app->telegramLog->send([
                'success',
                'Старые курсы очищены'
            ], 'rates');
        } else {
            Yii::$app->telegramLog->send([
                'error',
                'Нет старых курсов для очистки'
            ], 'rates');
        }
    }
    /**
     * Сервис для проверки статуса сервисов приложения 
     * Вызывается раз в полчаса и проверяет статус сервисов
     * При нахождении ошибки в сервисе, отправляется сообщение в телеграм
     * @OA\Get(
     *     path="/cron/check-pulse",
     *     summary="Проверка статуса сервисов приложения",
     *     @OA\Response(response="200", description="Сервисы проверены"),
     * )
     * @return null
     */

    public function actionCheckPulse()
    {
        Yii::$app->heartbeat->addHeartbeat('check-pulse', 'success');

        $threshold = date(
            'Y-m-d H:i:s',
            strtotime('-30 minutes')
        );

        $errors = Heartbeat::find()
            ->where(['status' => 'error'])
            ->andWhere(['>', 'last_run_at', $threshold])
            ->all();

        if (!empty($errors)) {
            $uniqueServices = array_unique(array_column($errors, 'service_name'));
            $message = "В следующих сервисах есть ошибки: \n " . implode(', ', array_map(function ($service) {
                return $this->services[$service] ?? $service;
            }, $uniqueServices));
            Yii::$app->telegramLog->send(
                'error',
                [
                    'Обнаружены ошибки в сервисах:',
                    $message
                ]
            );
            Yii::$app->actionLog->error('Обнаружены ошибки в сервисах: ' . implode(', ', $uniqueServices));
        }

        Yii::$app->response->format = yii\web\Response::FORMAT_JSON;
        Yii::$app->actionLog->success('Проверка сервисов завершена');

        Yii::$app->telegramLog->send(
            'success',
            'Проверка сервисов завершена'
        );
        
        return [
            'status' => 'success',
            'message' => 'Проверка сервисов завершена',
        ];
    }

    /**
     * @OA\Get(
     *     path="/cron/clear-action-logs",
     *     summary="Очистить логи действий",
     *     @OA\Response(response="200", description="Логи действий очищены"),
     *     @OA\Response(response="500", description="Ошибка очистки логов действий")
     * )
     */
    public function actionClearActionLogs()
    {
        $logFile = __DIR__ . '/../runtime/logs/action.log';

        if (file_exists($logFile)) {
            if (file_put_contents($logFile, '') !== false) {
                Yii::$app->actionLog->success('Логи действий очищены');
                return ['status' => 'success', 'message' => 'Логи действий очищены'];
            } else {
                Yii::$app->actionLog->error('Ошибка очистки логов действий');
                return ['status' => 'error', 'message' => 'Ошибка очистки логов действий'];
            }
        } else {
            Yii::$app->actionLog->error('Файл логов действий не найден');
            return ['status' => 'error', 'message' => 'Файл логов действий не найден'];
        }
    }

    /**
     * @OA\Get(
     *     path="/cron/cleanup-guest-accounts",
     *     summary="Очистка гостевых аккаунтов",
     *     @OA\Response(response="200", description="Гостевые аккаунты очищены"),
     *     @OA\Response(response="500", description="Ошибка очистки гостевых аккаунтов")
     * )
     */
    public function actionCleanupGuestAccounts()
    {
        try {
            $db = Yii::$app->db;
            $transaction = $db->beginTransaction();

            // Получаем ID гостевых пользователей
            $guestUserIds = $db->createCommand('
                SELECT id FROM user 
                WHERE role = :clientDemo 
                OR role = :buyerDemo
            ', [
                ':clientDemo' => 'client-demo',
                ':buyerDemo' => 'buyer-demo'
            ])->queryColumn();

            if (empty($guestUserIds)) {
                Yii::$app->actionLog->success('Гостевые учетные записи не найдены');
                return ['status' => 'success', 'message' => 'Гостевые учетные записи не найдены'];
            }

            $userIdsStr = implode(',', $guestUserIds);

            // Удаляем связанные данные в правильном порядке
            $tables = [
                'feedback_buyer_link_attachment' => 'feedback_buyer_id IN (SELECT id FROM feedback_buyer WHERE buyer_id IN (' . $userIdsStr . '))',
                'feedback_buyer' => 'buyer_id IN (' . $userIdsStr . ')',
                'buyer_delivery_offer' => 'buyer_id IN (' . $userIdsStr . ')',
                'buyer_offer' => 'buyer_id IN (' . $userIdsStr . ')',
                'order_distribution' => 'current_buyer_id IN (' . $userIdsStr . ')',
                'chats' => 'user_id IN (' . $userIdsStr . ')',
                'order' => 'created_by IN (' . $userIdsStr . ') OR buyer_id IN (' . $userIdsStr . ')',
                'user_link_category' => 'user_id IN (' . $userIdsStr . ')',
                'user_link_type_delivery' => 'user_id IN (' . $userIdsStr . ')',
                'user_link_type_packaging' => 'user_id IN (' . $userIdsStr . ')',
                'user_settings' => 'user_id IN (' . $userIdsStr . ')',
                'user_verification_request' => 'created_by_id IN (' . $userIdsStr . ') OR manager_id IN (' . $userIdsStr . ') OR approved_by_id IN (' . $userIdsStr . ')',
                'notification' => 'user_id IN (' . $userIdsStr . ')',
                'push_notification' => 'client_id IN (' . $userIdsStr . ')',
                'user' => 'id IN (' . $userIdsStr . ')'
            ];

            $deletedCounts = [];
            foreach ($tables as $table => $condition) {
                $count = $db->createCommand()
                    ->delete($table, $condition)
                    ->execute();
                $deletedCounts[$table] = $count;
            }

            $transaction->commit();

            // Логируем результаты
            $message = "Успешно удалено:\n";
            foreach ($deletedCounts as $table => $count) {
                $message .= "- Из таблицы {$table}: {$count} записей\n";
            }

            Yii::$app->actionLog->success($message);
            Yii::$app->telegramLog->send('success', $message, 'cleanup-guest-accounts');

            return [
                'status' => 'success',
                'message' => 'Гостевые аккаунты успешно очищены',
                'details' => $deletedCounts
            ];

        } catch (\Exception $e) {
            if (isset($transaction)) {
                $transaction->rollBack();
            }
            
            $errorMessage = 'Ошибка при очистке гостевых аккаунтов: ' . $e->getMessage();
            Yii::$app->actionLog->error($errorMessage);
            Yii::$app->telegramLog->send('error', $errorMessage, 'cleanup-guest-accounts');
            
            return [
                'status' => 'error',
                'message' => $errorMessage
            ];
        }
    }
}
