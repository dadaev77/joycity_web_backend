<?php

namespace app\services;

use app\services\UserActionLogService as Log;
use app\models\OrderDistribution;

use Yii;

class CronDistributionService
{
    public function __construct()
    {
        Log::info('CronDistributionService start');
    }

    public function __destruct()
    {
        Log::info('CronDistributionService end');
    }

    public static function createCronJob(OrderDistribution $task)
    {
        Log::info('Create cron job for task ' . $task->id);
    }
}
