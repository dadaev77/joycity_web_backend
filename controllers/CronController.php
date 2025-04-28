<?php

namespace app\controllers;

use Yii;
use app\models\OrderDistribution;
use app\models\Rate;
use yii\web\Controller;
use app\services\ExchangeRateService;
use app\models\Heartbeat;
use yii\db\Query;

class CronController extends Controller
{
    private $services = [
        'rates' => 'Курсы валют',
        'distribution' => 'Распределение заказов байеров',
        'cleanup-guest-accounts' => 'Очистка гостевых аккаунтов: все пользователи с ролями, содержащими "demo"',
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
            $rate->USD = round($rates['data']['USD'], 4);
            $rate->CNY = round($rates['data']['CNY'], 4);

            if ($rate->save()) {
                Yii::$app->heartbeat->addHeartbeat('rates', 'success');
                Yii::$app->telegramLog->send(
                    'success',
                    [
                        'Курсы обновлены:',
                        'USD - ' . $rates['data']['USD'] . ', CNY - ' . $rates['data']['CNY'],
                        'Курсы: USD - ' . $rate->USD . ', CNY - ' . $rate->CNY,
                    ],
                    'rates'
                );
                return ['status' => 'success', 'message' => 'Курсы обновлены'];
            } else {
                Yii::$app->telegramLog->send(
                    'error',
                    'Ошибка сохранения курсов',
                    'rates'
                );
                return ['status' => 'error', 'message' => 'Ошибка сохранения курсов'];
            }
        }

        Yii::$app->telegramLog->send(
            'error',
            'Нет данных для обновления курсов',
            'rates'
        );
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
     *     summary="Очистка аккаунтов с определенными ролями через 24 часа",
     *     @OA\Response(response="200", description="Аккаунты очищены"),
     *     @OA\Response(response="500", description="Ошибка очистки аккаунтов")
     * )
     */
    public function actionCleanupGuestAccounts()
    {
        try {
            $transaction = Yii::$app->db->beginTransaction();

            // Получаем ID всех пользователей с role_id 4 и 7, созданных более 24 часов назад
            $guestUserIds = (new Query())
                ->select('id')
                ->from('user')
                ->where(['in', 'role_id', [4, 7]])
                ->andWhere(['<', 'created_at', date('Y-m-d H:i:s', strtotime('-24 hours'))])
                ->column();

            if (empty($guestUserIds)) {
                Yii::$app->actionLog->success('Учетные записи с указанными ролями, созданные более 24 часов назад, не найдены');
                return ['status' => 'success', 'message' => 'Учетные записи с указанными ролями, созданные более 24 часов назад, не найдены'];
            }

            // Экранируем ID (на случай если где-то будет использоваться implode)
            $guestUserIds = array_map('intval', $guestUserIds);

            // Список таблиц и условий для удаления
            $tables = [
                'feedback_buyer_link_attachment' => ['in', 'feedback_buyer_id', (new Query())->select('id')->from('feedback_buyer')->where(['in', 'buyer_id', $guestUserIds])],
                'feedback_buyer' => ['in', 'buyer_id', $guestUserIds],
                'buyer_delivery_offer' => ['in', 'buyer_id', $guestUserIds],
                'buyer_offer' => ['in', 'buyer_id', $guestUserIds],
                'order_distribution' => ['in', 'current_buyer_id', $guestUserIds],
                'chats' => ['in', 'user_id', $guestUserIds],
                'order' => [
                    'or',
                    ['in', 'created_by', $guestUserIds],
                    ['in', 'buyer_id', $guestUserIds],
                ],
                'user_link_category' => ['in', 'user_id', $guestUserIds],
                'user_link_type_delivery' => ['in', 'user_id', $guestUserIds],
                'user_link_type_packaging' => ['in', 'user_id', $guestUserIds],
                'user_verification_request' => [
                    'or',
                    ['in', 'created_by_id', $guestUserIds],
                    ['in', 'manager_id', $guestUserIds],
                    ['in', 'approved_by_id', $guestUserIds],
                ],
                'notification' => ['in', 'user_id', $guestUserIds],
                'push_notification' => ['in', 'client_id', $guestUserIds],
                'user_settings' => ['in', 'user_id', $guestUserIds],
                'user' => ['in', 'id', $guestUserIds],
            ];

            $deletedCounts = [];
            foreach ($tables as $table => $condition) {
                $deletedCounts[$table] = $this->deleteFromTable($table, $condition);
            }

            $transaction->commit();

            // Формируем лог
            $message = "Успешно удалено:\n";
            $totalDeleted = 0;
            foreach ($deletedCounts as $table => $count) {
                $message .= "- Из таблицы {$table}: {$count} записей\n";
                $totalDeleted += $count;
            }

            Yii::$app->actionLog->success($message);
            Yii::$app->telegramLog->send(
                'success',
                "Очистка аккаунтов с определенными ролями\n" .
                    "Удалены все пользователи с role_id 4 и 7, созданные более 24 часов назад\n" .
                    "Всего удалено записей: " . $totalDeleted,
            );

            return [
                'status' => 'success',
                'message' => 'Аккаунты с указанными ролями, созданные более 24 часов назад, успешно очищены',
                'details' => $deletedCounts
            ];
        } catch (\Throwable $e) {
            if (isset($transaction)) {
                $transaction->rollBack();
            }

            $errorMessage = 'Ошибка при очистке аккаунтов: ' . $e->getMessage();
            Yii::$app->actionLog->error($errorMessage);
            Yii::$app->telegramLog->send(
                'error',
                "Ошибка при очистке аккаунтов\n" .
                    "Детали ошибки: " . $e->getMessage() . "\n" .
                    "Время: " . date('Y-m-d H:i:s'),
            );

            return [
                'status' => 'error',
                'message' => $errorMessage
            ];
        }
    }

    /**
     * Удаляет записи из таблицы по заданному условию
     * 
     * @param string $table Имя таблицы
     * @param array $condition Условие для удаления
     * @return int Количество удаленных записей
     */
    private function deleteFromTable($table, $condition)
    {
        return Yii::$app->db->createCommand()
            ->delete($table, $condition)
            ->execute();
    }
}
