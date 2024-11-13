<?php

namespace app\controllers;

use Yii;
use app\services\UserActionLogService as Log;
use app\models\OrderDistribution;
use app\models\Rate;
use yii\web\Controller;
use app\services\ExchangeRateService;

class CronController extends Controller
{
    public function init()
    {
        parent::init();
        Log::setController('CronController');
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
        if (!$taskID) return;
        $command = '* * * * * curl -X GET "' . $_ENV['APP_URL'] . '/cron/distribution?taskID=' . $taskID . '"';
        exec(" crontab -l | { cat; echo '$command'; } | crontab - ");
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
        // Log::log('Distribution task started');
        $actualTask = OrderDistribution::find()->where(['id' => $taskID])->one();

        if (
            !$actualTask ||
            $actualTask->status !== OrderDistribution::STATUS_IN_WORK
        ) {
            Log::danger('Задача не найдена или статус не "в работе". Удаление задания из списка');
            $command = "crontab -l | grep -v 'taskID={$taskID}' | crontab -";
            exec($command);
            return;
        }
        // Log::success('Task found. ID: ' . $actualTask->id);
        $buyers = explode(',', $actualTask->buyer_ids_list);
        $currentBuyer = $actualTask->current_buyer_id;
        $nextBuyer = $this->getNextBuyer($buyers, $currentBuyer);
        $actualTask->current_buyer_id = $nextBuyer;
        // Log::success('Текущий ID покупателя для задачи ' . $actualTask->id . ' : ' . $nextBuyer);
        if (!$actualTask->save()) {
            Log::danger('Ошибка сохранения задачи');
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
            $rate->save();
        }
        return $rate;
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
}
