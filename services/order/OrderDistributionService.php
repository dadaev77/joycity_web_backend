<?php

namespace app\services\order;

use app\components\responseFunction\Result;
use app\components\responseFunction\ResultAnswer;
use app\models\Order;
use app\models\OrderDistribution;
use app\models\User;
use Yii;

class OrderDistributionService
{
    public const DISTRIBUTION_SCRIPT_TIMEOUT = 300;
    public const DISTRIBUTION_ACCEPT_TIMEOUT = 65;

    public static function createDistributionTask(
        int $orderId,
        int $onlyBuyerId = 0,
    ): ResultAnswer {
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
            return Result::success($task);
        }

        return Result::errors($task->getFirstErrors());
    }

    public static function reloadDistributionTask(int $orderId): ResultAnswer
    {
        $task = OrderDistribution::findOne([
            'order_id' => $orderId,
            'status' => OrderDistribution::STATUS_ACCEPTED,
        ]);

        if (!$task) {
            return Result::notFound();
        }

        $task->status = OrderDistribution::STATUS_IN_WORK;

        return self::moveTaskToNextBuyer($task, true);
    }

    public function distribute(
        int $scriptTimeout = self::DISTRIBUTION_SCRIPT_TIMEOUT,
    ): void {
        $endTimestamp = time() + $scriptTimeout;

        while (time() < $endTimestamp) {
            $expiredTime = date(
                'Y-m-d H:i:s',
                time() - self::DISTRIBUTION_ACCEPT_TIMEOUT,
            );
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

    public static function buyerAccept(
        OrderDistribution $task,
        int $buyerId,
    ): ResultAnswer {
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

    public static function buyerDecline(OrderDistribution $task): ResultAnswer
    {
        return self::moveTaskToNextBuyer($task, true);
    }

    private static function moveTaskToNextBuyer(
        OrderDistribution $task,
        bool $force = false,
    ): ResultAnswer {
        if ($task->status !== OrderDistribution::STATUS_IN_WORK) {
            return Result::success($task);
        }

        if (
            !$force &&
            strtotime($task->requested_at) + self::DISTRIBUTION_ACCEPT_TIMEOUT >
            time()
        ) {
            return Result::notValid([
                'requested_at' => 'Task is not ready to transfer',
            ]);
        }

        $availableBuyers = explode(',', $task->buyer_ids_list);
        $currentBuyerIndex = array_search(
            (string) $task->current_buyer_id,
            $availableBuyers,
            true,
        );

        if (
            in_array(
                $task->order->status,
                array_merge(
                    Order::STATUS_GROUP_REQUEST_CLOSED,
                    Order::STATUS_GROUP_ORDER_CLOSED,
                ),
                true,
            )
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

            if (!$orderCloseStatus->success) {
                return $orderCloseStatus;
            }
        } else {
            $task->current_buyer_id = $availableBuyers[$currentBuyerIndex + 1];
        }

        $task->requested_at = date('Y-m-d H:i:s');

        if ($task->save()) {
            return Result::success($task);
        }

        return Result::errors($task->getFirstErrors());
    }

    private static function createBuyersList(Order $order): string
    {
        $out = [];
        $categoryId = $order->subcategory->category_id;
        $buyerIds = User::find()
            ->select(['id', 'rating'])
            ->with([
                'categories' => fn ($q) => $q->where(['id' => $categoryId]),
                'userSettings' => fn ($q) => $q->select([
                    'id',
                    'user_id',
                    'use_only_selected_categories',
                ]),
            ])
            ->where(['role' => User::ROLE_BUYER])
            ->orderBy(['rating' => SORT_DESC]);

        var_dump($buyerIds);
        die();

        foreach ($buyerIds->each() as $buyer) {
            if (
                ($buyer->userSettings->use_only_selected_categories &&
                    $buyer->categories) ||
                !$buyer->userSettings->use_only_selected_categories
            ) {
                $out[] = $buyer->id;
            }

            gc_collect_cycles();
        }

        return implode(',', $out);
    }
}
