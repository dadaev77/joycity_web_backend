<?php

namespace app\services;

use app\models\OrderDistribution;
use app\services\UserActionLogService as Log;
use Yii;

class CronService
{
    public static function run()
    {
        Log::setController('CronService');
    }
    public static function runDistribution($taskId)
    {
        Log::info('Running distribution for task: ' . $taskId);
        exec("curl -X GET " . $_ENV['APP_URL'] . "/cron/distribute-task?taskId=$taskId");
    }
}
