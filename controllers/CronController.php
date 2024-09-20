<?php

namespace app\controllers;

use yii\web\Controller;
use app\models\OrderDistribution;
use app\services\UserActionLogService as Log;

class CronController extends Controller
{
    public function actionDistributeTask($taskId)
    {
        $actualTask = OrderDistribution::findOne($taskId);
        if (!$actualTask) {
            Log::error('Task with id ' . $taskId . ' not found');
            return;
        }

        $buyerIds = explode(',', $actualTask->buyer_ids_list);
        foreach ($buyerIds as $key => $buyerId) {
            $actualTask->current_buyer_id = $buyerId;
            $actualTask->status = OrderDistribution::STATUS_IN_WORK;
            $actualTask->save();
            if (!$actualTask->save()) {
                Log::error('Failed to save task: ' . json_encode($actualTask->getErrors()));
            }
            Log::info('Task ' . $actualTask->id . ' assigned to buyer ' . $buyerId);
            sleep(10);
            unset($buyerIds[$key]);
        }
        $actualTask->status = OrderDistribution::STATUS_CLOSED;
        $actualTask->save();
        Log::info('Task ' . $actualTask->id . ' closed');
    }
}
