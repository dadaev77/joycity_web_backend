<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\services\order\OrderDistributionService;
use Throwable;
use yii\console\Controller;

class CronController extends Controller
{
    public function actionOrderDistribution()
    {
        $endTime = time() + OrderDistributionService::DISTRIBUTION_SCRIPT_TIMEOUT;

        for ($i = 0; $i < 5; $i++) {
            $timeout = $endTime - time();

            try {
                (new OrderDistributionService())->distribute($timeout);
            } catch (Throwable) {
            }
        }
    }
}
