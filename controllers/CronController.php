<?php

namespace app\controllers;

use Yii;
use app\services\UserActionLogService as Log;
use app\models\OrderDistribution;
use yii\web\Controller;
use app\services\ExchangeRateService;

class CronController extends Controller
{
    public function init()
    {
        parent::init();
        Log::setController('CronController');
    }
    public function actionCreate(string $taskID = null)
    {
        if (!$taskID) return;
        $command = '* * * * * curl -X GET "' . $_ENV['APP_URL'] . '/cron/distribution?taskID=' . $taskID . '"';
        exec(" crontab -l | { cat; echo '$command'; } | crontab - ");
    }
    public function actionDistribution(string $taskID = null)
    {
        Log::log('Distribution task started');
        $actualTask = OrderDistribution::find()->where(['id' => $taskID])->one();

        if (
            !$actualTask ||
            $actualTask->status !== OrderDistribution::STATUS_IN_WORK
        ) {
            Log::danger('Task not found or status is not "in_work. Removing job from list"');
            $command = "crontab -l | grep -v 'taskID={$taskID}' | crontab -";
            exec($command);
            return;
        }
        Log::success('Task found. ID: ' . $actualTask->id);
        $buyers = explode(',', $actualTask->buyer_ids_list);
        $currentBuyer = $actualTask->current_buyer_id;
        $nextBuyer = $this->getNextBuyer($buyers, $currentBuyer);
        $actualTask->current_buyer_id = $nextBuyer;
        Log::success('Current buyer id for task ' . $actualTask->id . ' is: ' . $nextBuyer);
        if (!$actualTask->save()) {
            Log::danger('Error saving task');
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
}
