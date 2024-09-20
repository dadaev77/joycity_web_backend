<?php

namespace app\services\order;

use app\components\responseFunction\Result;
use app\components\responseFunction\ResultAnswer;
use app\models\Order;
use app\models\OrderDistribution;
use app\models\User;
use app\services\CronDistributionService;
use app\services\UserActionLogService as Log;
use Yii;

class OrderDistributionService
{
    public const DISTRIBUTION_SCRIPT_TIMEOUT = 300;
    public const DISTRIBUTION_ACCEPT_TIMEOUT = 65;

    /**
     * Creates a distribution task for the given order and assigns a buyer based on the specified criteria.
     *
     * If the `onlyBuyerId` parameter is provided, it will assign the specified buyer to the order.
     * Otherwise, it will create a list of eligible buyers based on category preferences and settings,
     * and assign the first buyer from the list to the order.
     *
     * @param int $orderId The ID of the order for which to create the distribution task.
     * @param int $onlyBuyerId (optional) The ID of the buyer to assign to the order. If not provided, a buyer will be assigned based on criteria.
     * @return ResultAnswer The result of the operation, containing the created distribution task or an error message.
     */
    public static function createDistributionTask(int $orderId, int $onlyBuyerId = 0): ResultAnswer
    {

        $buyersList = $onlyBuyerId
            ? (string) $onlyBuyerId
            : self::createBuyersList(Order::findOne($orderId));

        $task = new OrderDistribution([
            'order_id' => $orderId,
            'current_buyer_id' => explode(',', $buyersList)[0],
            'requested_at' => date('Y-m-d H:i:s'),
            'status' => OrderDistribution::STATUS_IN_WORK,
            'buyer_ids_list' => $buyersList,
        ]);

        if ($task->save()) {
            Log::info('Distribution task created for order: ' . $orderId);
            CronDistributionService::createCronJob($task);
            Log::info('Call distribution job for task: ' . $task->id);
            return Result::success($task);
        }

        return Result::errors($task->getFirstErrors());
    }
    /**
     * Reloads a distribution task by setting its status to "in work" and reassigning the buyer.
     *
     * @param int $orderId The ID of the order associated with the distribution task.
     * @return ResultAnswer The result of the operation, containing the updated distribution task or an error message.
     */

    public static function reloadDistributionTask(int $orderId): ResultAnswer
    {
        $task = OrderDistribution::findOne([
            'order_id' => $orderId,
            'status' => OrderDistribution::STATUS_ACCEPTED,
        ]);
        if (!$task) return Result::notFound();
        $task->status = OrderDistribution::STATUS_IN_WORK;
        return self::moveTaskToNextBuyer($task, true);
    }


    /**
     * Distributes orders to buyers based on their availability and preferences.
     *
     * The method continuously checks for distribution tasks that have expired and are in the "in work" status.
     * For each expired task, it calls the `moveTaskToNextBuyer` method to reassign the buyer and update the task status.
     *
     * @param int $scriptTimeout The maximum time (in seconds) for the distribution process to run.
     * @return void
     */

    public function distribute(int $scriptTimeout = self::DISTRIBUTION_SCRIPT_TIMEOUT): void
    {
        $endTimestamp = time() + $scriptTimeout;
        while (time() < $endTimestamp) {
            $expiredTime = date('Y-m-d H:i:s', time() - self::DISTRIBUTION_ACCEPT_TIMEOUT);

            $activeTasks = OrderDistribution::find()
                ->with(['order'])
                ->where(['status' => OrderDistribution::STATUS_IN_WORK])
                ->andWhere(['<=', 'requested_at', $expiredTime]);

            foreach ($activeTasks->each() as $activeTask) {
                $status = self::moveTaskToNextBuyer($activeTask);

                if (!$status->success) {
                    echo date('Y-m-d H:i:s') .
                        ' Error update task, errors: ' .
                        json_encode($status->reason, JSON_THROW_ON_ERROR) .
                        PHP_EOL;
                }
            }
            gc_collect_cycles();
            sleep(5);
        }
    }


    /**
     * Handles the buyer's acceptance of the order by updating the distribution task and the order status.
     *
     * If the distribution task's status is not "in work", it returns an error message indicating that the task has already been handled.
     * If the order's status is not "created", it returns an error message indicating that the order is not in a valid state for acceptance.
     *
     * Otherwise, it updates the distribution task's buyer ID, status, and the order's buyer ID.
     * It also starts a database transaction to ensure data consistency.
     * If any of the updates fail, it rolls back the transaction and returns an error message containing the validation errors.
     * If all updates are successful, it commits the transaction and returns a success message.
     *
     * @param OrderDistribution $task The distribution task associated with the order.
     * @param int $buyerId The ID of the buyer who accepted the order.
     * @return ResultAnswer The result of the operation, containing a success message or an error message.
     */

    public static function buyerAccept(OrderDistribution $task, int $buyerId): ResultAnswer
    {
        if ($task->status !== OrderDistribution::STATUS_IN_WORK) {
            return Result::errors(['base' => 'Already handled']);
        }
        if ($task->order->status !== Order::STATUS_CREATED) {
            return Result::errors([
                'order' => 'Order is not in created status',
            ]);
        }
        $task->current_buyer_id = $buyerId;
        $task->status = OrderDistribution::STATUS_ACCEPTED;
        $task->order->buyer_id = $buyerId;
        $transaction = Yii::$app->db->beginTransaction();
        if (!$task->save()) {
            $transaction?->rollBack();
            return Result::errors($task->getFirstErrors());
        }
        if (!$task->order->save()) {
            $transaction?->rollBack();
            return Result::errors($task->order->getFirstErrors());
        }
        $transaction?->commit();
        return Result::success();
    }


    /**
     * Handles the buyer's decline of the order by reassigning the buyer and updating the distribution task status.
     *
     * It calls the `moveTaskToNextBuyer` method with the `force` parameter set to `true` to ensure that the buyer is reassigned.
     *
     * @param OrderDistribution $task The distribution task associated with the order.
     * @return ResultAnswer The result of the operation, containing a success message or an error message.
     */

    public static function buyerDecline(OrderDistribution $task): ResultAnswer
    {
        return self::moveTaskToNextBuyer($task, true);
    }


    /**
     * Moves the task to the next buyer based on the current buyer's decision and the specified conditions.
     *
     * If the task's status is not "in work", it returns the task as is.
     * If the `force` parameter is set to `false` and the task's requested at time plus the distribution accept timeout is greater than the current time,
     * it returns a not valid result with a message indicating that the task is not ready to transfer.
     *
     * It retrieves the list of available buyers from the task's buyer IDs list.
     * Depending on the current buyer's index in the list and the order's status, it updates the task's current buyer ID and status.
     * If the order's status is in the closed groups, it sets the task's status to "closed".
     * If the current buyer is the last buyer in the list, it also sets the task's status to "closed".
     * Otherwise, it updates the task's current buyer ID to the next buyer in the list and the requested at time.
     *
     * If the task's save operation is successful, it returns a success result with the updated task.
     * Otherwise, it returns an error result with the task's validation errors.
     *
     * @param OrderDistribution $task The distribution task associated with the order.
     * @param bool $force (optional) If set to `true`, it forces the task to move to the next buyer without considering the requested at time.
     * @return ResultAnswer The result of the operation, containing a success message or an error message.
     */

    private static function moveTaskToNextBuyer(OrderDistribution $task, bool $force = false): ResultAnswer
    {
        if ($task->status !== OrderDistribution::STATUS_IN_WORK) return Result::success($task);
        if (!$force && strtotime($task->requested_at) + self::DISTRIBUTION_ACCEPT_TIMEOUT > time()) return Result::notValid(['requested_at' => 'Task is not ready to transfer',]);
        $availableBuyers = explode(',', $task->buyer_ids_list);
        $currentBuyerIndex = array_search((string) $task->current_buyer_id, $availableBuyers, true);

        if (
            in_array($task->order->status, array_merge(Order::STATUS_GROUP_REQUEST_CLOSED, Order::STATUS_GROUP_ORDER_CLOSED), true,)
        ) {
            $task->status = OrderDistribution::STATUS_CLOSED;
            return $task->save()
                ? Result::success($task)
                : Result::errors($task->getFirstErrors());
        }

        if ($currentBuyerIndex === count($availableBuyers) - 1) {
            $task->status = OrderDistribution::STATUS_CLOSED;
            // todo #cron_tasks close chat and lead
            $orderCloseStatus = OrderStatusService::cancelled($task->order_id);
            if (!$orderCloseStatus->success) return $orderCloseStatus;
        } else {
            $task->current_buyer_id = $availableBuyers[$currentBuyerIndex + 1];
        }
        $task->requested_at = date('Y-m-d H:i:s');

        if ($task->save()) return Result::success($task);
        return Result::errors($task->getFirstErrors());
    }

    /**
     * Creates a list of buyer IDs eligible to receive an order based on category preferences and settings.
     *
     * @param Order $order The order for which to create the buyer list.
     * @return mixed The list of buyer IDs as a comma-separated string.
     */

    private static function createBuyersList(Order $order): mixed
    {
        $out = [];
        $categoryId = $order->subcategory->category_id;

        $buyerIds = User::find()
            ->select(['id', 'rating'])
            ->with([
                'categories' => fn($q) => $q->where(['id' => $categoryId]),
                'userSettings' => fn($q) => $q->select([
                    'id',
                    'user_id',
                    'use_only_selected_categories',
                ]),
            ])
            ->where(['role' => User::ROLE_BUYER])
            ->orderBy(['rating' => SORT_DESC]);

        foreach ($buyerIds->all() as $buyer) {
            if (($buyer->userSettings->use_only_selected_categories && $buyer->categories) || !$buyer->userSettings->use_only_selected_categories) {
                $out[] = $buyer->id;
            }
        }

        return implode(',', $out);
    }
}
