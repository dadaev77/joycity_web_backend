<?php

namespace app\services;

use app\services\UserActionLogService as Log;
use app\models\OrderDistribution;

use Yii;

class CronDistributionService
{
    public function __construct()
    {
        Log::info('Distribution task start');
    }

    public function __destruct()
    {
        Log::info('Distribution task end');
    }

    public static function createCronJob(OrderDistribution $task)
    {
        $buyers = explode(',', $task->buyer_ids_list);

        foreach ($buyers as $key => $buyerId) {
            Log::info('current buyer id for task: ' . $task->id . ' is ' . $buyerId);
            $task->current_buyer_id = $buyerId;
            $task->save();
            sleep(60);
            unset($buyers[$key]);
        }

        $task->status = OrderDistribution::STATUS_COMPLETED;
        $task->save();
    }
}
