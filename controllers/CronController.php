<?php

namespace app\controllers;

use app\components\ApiResponse;
use yii\web\Controller;
use app\models\OrderDistribution;
use app\services\UserActionLogService as Log;
use Yii;

class CronController extends Controller
{
    public function init()
    {
        parent::init();
        Log::setController('CronController');
    }
    /**
     * Distributes the task to the next buyer.
     *
     * @param int $taskId The ID of the task to distribute.
     */
    public function actionDistributeTask($taskId)
    {
        $actualTask = OrderDistribution::find()->where(['id' => $taskId])->one();

        if (!$actualTask) {
            Log::warning("Task not found: $taskId");
            return Yii::$app->response->setStatusCode(404, 'Task not found');
        }

        $buyersList = explode(',', $actualTask->buyer_ids_list);

        Log::info("Buyers list: " . implode(', ', $buyersList));

        foreach ($buyersList as $buyerId) {
            $command = "echo '* * * * * curl " . $_ENV['APP_URL'] . "/cron/dist-between-buyers?taskId=$taskId' | crontab -";
            exec($command);
        }
    }

    /**
     * Distributes the task between buyers.
     *
     * @param int $taskId The ID of the task to distribute.
     */
    public function actionDistBetweenBuyers($taskId)
    {
        Log::info("Distributing between buyers for task ID: $taskId");

        $currentTask = OrderDistribution::findOne($taskId);
        if (!$currentTask) {
            Log::warning("Task not found: $taskId");
            return Yii::$app->response->setStatusCode(404, 'Task not found');
        }

        $currentBuyerId = $currentTask->current_buyer_id;
        $buyersList = explode(',', $currentTask->buyer_ids_list);
        Log::info("Current buyer ID: $currentBuyerId");

        $nextBuyerId = $this->getNextBuyerId($buyersList, $currentBuyerId);
        // if (!$nextBuyerId) {
        //     Log::info("No next buyer found, removing cron job.");
        //     exec('crontab -r');
        //     return;
        // }

        $currentTask->current_buyer_id = $nextBuyerId;

        if (!$currentTask->save()) {
            Log::error("Failed to update current buyer ID: " . implode(', ', $currentTask->getErrors()));
            return;
        };

        Log::info("Updated current buyer ID to: $nextBuyerId");
    }
    private function getNextBuyerId($buyersList, $currentBuyerId)
    {
        $currentIndex = array_search($currentBuyerId, $buyersList);
        $nextIndex = $currentIndex + 1;

        if ($nextIndex == null || $nextIndex >= count($buyersList)) {
            return $buyersList[0];
        }

        return $buyersList[$nextIndex];
    }
}
