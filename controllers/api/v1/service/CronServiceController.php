<?php

namespace app\controllers\api\v1\service;

use app\components\responseFunction\Result;
use app\components\responseFunction\ResultAnswer;
use app\models\Order;
use app\models\OrderDistribution;
use app\models\User;
use app\services\UserActionLogService as Log;

class CronServiceController extends BaseController
{
    public function index()
    {
        Log::info('CronServiceController index');
    }
}
