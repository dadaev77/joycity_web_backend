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
     *     @OA\Response(response="200", description="Задача cron создана"),
     *     @OA\Response(response="400", description="Неверный ID задачи")
     * )
     */
    public static function actionCreate($taskID = null)
    {
        $task = OrderDistribution::find()->where(['id' => $taskID])->one();
        $command = '* * * * * curl -X GET "' . $_ENV['APP_URL'] . '/cron/distribution?taskID=' . $taskID . '"';

        if (!$task) {
            Yii::$app->actionLog->error('Несуществующий ID задачи: ' . $taskID);
            return false;
        }
        try {
            if (exec(" crontab -l | { cat; echo '$command'; } | crontab - ")) {
                Yii::$app->actionLog->success('Задача cron создана: ' . $taskID);
                return true;
            } else {
                Yii::$app->actionLog->error('Ошибка создания задачи cron: ' . $taskID);
                return false;
            }
        } catch (\Exception $e) {
            Yii::$app->actionLog->error('Ошибка создания задачи cron: ' . $taskID);
            Yii::$app->telegramLog->send('error', 'Ошибка создания задачи cron: ' . $taskID);
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
            $rate->USD = round($rates['data']['USD'] * 1.02, 4);
            $rate->CNY = round($rates['data']['CNY'] * 1.05, 4);

            if ($rate->save()) {
                Yii::$app->heartbeat->addHeartbeat('rates', 'success');
                Yii::$app->telegramLog->send('success', 'Курсы обновлены: USD - ' . $rates['data']['USD'] . ' CNY - ' . $rates['data']['CNY']);
                return ['status' => 'success', 'message' => 'Курсы обновлены'];
            } else {
                Yii::$app->telegramLog->send('error', 'Ошибка сохранения курсов');
                return ['status' => 'error', 'message' => 'Ошибка сохранения курсов'];
            }
        }

        Yii::$app->telegramLog->send('error', 'Нет данных для обновления курсов');
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
            Yii::$app->actionLog->success('Старые курсы очищены');
        } else {
            Yii::$app->actionLog->error('Нет старых курсов для очистки');
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
            Yii::$app->telegramLog->send('error', $message);
            Yii::$app->actionLog->error('Обнаружены ошибки в сервисах: ' . implode(', ', $uniqueServices));
        }

        Yii::$app->response->format = yii\web\Response::FORMAT_JSON;
        Yii::$app->actionLog->success('Проверка сервисов завершена');
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
}
