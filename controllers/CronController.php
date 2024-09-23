<?php

namespace app\controllers;

use Yii;
use app\services\UserActionLogService as Log;
use yii\web\Controller;

class CronController extends Controller
{
    public function actionCreate(string $taskID = null)
    {
        if (!$taskID) return;
        $command = "* * * * * curl -X GET {$_ENV['APP_URL']}/cron/distribution?taskID={$taskID}";
        exec(" crontab -l | { cat; echo '$command'; } | crontab - ");
    }
    public function actionDistribution(string $taskID = null)
    {
        Log::success('CronController Distribution TaskID: ' . $taskID);
    }
}
