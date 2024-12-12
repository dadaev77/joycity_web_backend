<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use Throwable;
use yii\console\Controller;
use app\models\OrderDistribution;
use app\services\UserActionLogService as Log;
use app\services\ExchangeRateService;
use app\models\Rate;
use yii\db\Expression;

class CronController extends Controller
{
    public function actionOrderDistribution($taskId)
    {
        $actualTask = OrderDistribution::findOne($taskId);

        if (!$actualTask) echo 'Task not found';
        return;
        $buyerIds = explode(',', $actualTask->buyer_ids_list);

        foreach ($buyerIds as $key => $buyerId) {
            $actualTask->current_buyer_id = $buyerId;
            $actualTask->status = OrderDistribution::STATUS_IN_WORK;
            $actualTask->save();
            echo 'Task ' . $actualTask->id . ' assigned to buyer ' . $buyerId . PHP_EOL;
            sleep(10);
            unset($buyerIds[$key]);
        }
        $actualTask->status = OrderDistribution::STATUS_CLOSED;
        $actualTask->save();

        return;
    }

    /**
     * Updates exchange rates
     * Cron: * 10 * * * *
     */
    public function actionUpdateRates()
    {
        try {
            $rates = ExchangeRateService::getRate(['USD', 'CNY']);

            if (isset($rates['error'])) {
                echo "Error getting rates: " . $rates['error'] . "\n";
                return;
            }

            $rate = new Rate();
            $rate->RUB = 1.0000; // RUB is always 1
            $rate->CNY = round($rates['data']['CNY'] * 1.05, 4); // CNY + 5%
            $rate->USD = round($rates['data']['USD'] * 1.02, 4); // USD + 2%
            $rate->created_at = new Expression('NOW()');

            if ($rate->save()) {
                echo "Rates updated successfully\n";
            } else {
                echo "Error saving rates: " . print_r($rate->getErrors(), true) . "\n";
            }
        } catch (\Exception $e) {
            echo "Exception: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Clears old rates keeping only the latest one
     * Cron: 0 0 * * *
     */
    public function actionClearRates()
    {
        try {
            $latestRate = Rate::find()->orderBy(['id' => SORT_DESC])->one();

            if ($latestRate) {
                Rate::deleteAll(['<', 'id', $latestRate->id]);
                echo "Old rates cleared successfully\n";
            } else {
                echo "No rates found\n";
            }
        } catch (\Exception $e) {
            echo "Exception: " . $e->getMessage() . "\n";
        }
    }
}
