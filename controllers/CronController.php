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
    public function actionCreate(string $taskID = null)
    {
        if (!$taskID) return ['status' => 'error', 'message' => 'Неверный ID задачи'];
        $command = '* * * * * curl -X GET "' . $_ENV['APP_URL'] . '/cron/distribution?taskID=' . $taskID . '"';
        if (exec(" crontab -l | { cat; echo '$command'; } | crontab - ")) {
            return ['status' => 'success', 'message' => 'Задача cron создана'];
        } else {
            return ['status' => 'error', 'message' => 'Ошибка создания задачи cron'];
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
            return;
        }
        $buyers = explode(',', $actualTask->buyer_ids_list);
        $currentBuyer = $actualTask->current_buyer_id;
        $nextBuyer = $this->getNextBuyer($buyers, $currentBuyer);
        $actualTask->current_buyer_id = $nextBuyer;
        if (!$actualTask->save()) {
            return;
        }
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

            \Yii::$app->telegramLog->send('success', 'Курсы обновлены: USD - ' . $rates['data']['USD'] . ' CNY - ' . $rates['data']['CNY']);

            if ($rate->save()) {
                Yii::$app->heartbeat->addHeartbeat('rates', 'success');
                return ['status' => 'success', 'message' => 'Курсы обновлены'];
            } else {
                return ['status' => 'error', 'message' => 'Ошибка сохранения курсов'];
            }
            Yii::$app->heartbeat->addHeartbeat('rates', 'success');
        }

        Yii::$app->heartbeat->addHeartbeat('rates', 'error');
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
        }

        Yii::$app->response->format = yii\web\Response::FORMAT_JSON;
        return [
            'status' => 'success',
            'message' => 'Проверка сервисов завершена',
            // 'errors' => $errors
        ];
    }
}
