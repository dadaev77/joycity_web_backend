<?php

namespace app\services\order;

use app\components\responseFunction\Result;
use app\components\responseFunction\ResultAnswer;
use app\models\Chat;
use app\models\Notification;
use app\models\Order;
use app\services\chat\ChatArchiveService;
use app\services\notification\NotificationConstructor;
use app\services\notification\NotificationManagementService;
use Throwable;
use Yii;
use app\services\UserActionLogService as LogService;

class OrderStatusService
{
    public static function created(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(Order::STATUS_CREATED, $orderId);
    }

    public static function buyerAssigned(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(Order::STATUS_BUYER_ASSIGNED, $orderId);
    }

    public static function buyerOfferCreated(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(
            Order::STATUS_BUYER_OFFER_CREATED,
            $orderId,
        );
    }

    public static function buyerOfferAccepted(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(
            Order::STATUS_BUYER_OFFER_ACCEPTED,
            $orderId,
        );
    }

    public static function paid(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(Order::STATUS_PAID, $orderId);
    }

    public static function fulfillmentPackageLabelingComplete(
        int $orderId,
    ): ResultAnswer {
        return self::changeOrderStatus(
            Order::STATUS_FULFILLMENT_PACKAGE_LABELING_COMPLETE,
            $orderId,
        );
    }

    public static function readyTransferringToMarketplace(
        int $orderId,
    ): ResultAnswer {
        return self::changeOrderStatus(
            Order::STATUS_READY_TRANSFERRING_TO_MARKETPLACE,
            $orderId,
        );
    }

    public static function partiallyDeliveredToMarketplace(
        int $orderId,
    ): ResultAnswer {
        return self::changeOrderStatus(
            Order::STATUS_PARTIALLY_DELIVERED_TO_MARKETPLACE,
            $orderId,
        );
    }

    public static function fullyDeliveredToMarketplace(
        int $orderId,
    ): ResultAnswer {
        return self::changeOrderStatus(
            Order::STATUS_FULLY_DELIVERED_TO_MARKETPLACE,
            $orderId,
        );
    }

    public static function partiallyPaid(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(Order::STATUS_PARTIALLY_PAID, $orderId);
    }

    public static function fullyPaid(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(Order::STATUS_FULLY_PAID, $orderId);
    }

    public static function transferringToBuyer(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(
            Order::STATUS_TRANSFERRING_TO_BUYER,
            $orderId,
        );
    }

    public static function arrivedToBuyer(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(
            Order::STATUS_ARRIVED_TO_BUYER,
            $orderId,
        );
    }

    public static function buyerInspectionComplete(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(
            Order::STATUS_BUYER_INSPECTION_COMPLETE,
            $orderId,
        );
    }

    public static function transferringToFulfilment(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(
            Order::STATUS_TRANSFERRING_TO_FULFILLMENT,
            $orderId,
        );
    }

    public static function fulfillmentInspectionComplete(
        int $orderId,
    ): ResultAnswer {
        return self::changeOrderStatus(
            Order::STATUS_FULFILLMENT_INSPECTION_COMPLETE,
            $orderId,
        );
    }

    public static function arrivedToFulfilment(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(
            Order::STATUS_ARRIVED_TO_FULFILLMENT,
            $orderId,
        );
    }

    public static function arrivedToWarehouse(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(
            Order::STATUS_ARRIVED_TO_WAREHOUSE,
            $orderId,
        );
    }

    public static function transferringToWarehouse(int $orderId): ResultAnswer
    {
        return self::changeOrderStatus(
            Order::STATUS_TRANSFERRING_TO_WAREHOUSE,
            $orderId,
        );
    }

    public static function completed(int $orderId): ResultAnswer
    {
        LogService::info('OrderStatusService. order status for change for order id: ' . $orderId);
        $order = Order::find()
            ->select(['id', 'status', 'created_by'])
            ->where(['id' => $orderId])
            ->one();

        if (!$order) {
            return Result::notFound();
        }
        LogService::success('OrderStatusService. order found');
        if (
            $order->status !== Order::STATUS_ARRIVED_TO_WAREHOUSE &&
            $order->status !== Order::STATUS_FULLY_PAID
        ) {
            return Result::errors([
                'status' => 'Order in current status cannot be completed',
            ]);
        }
        LogService::success('OrderStatusService. order status is allowed for completion');

        $linkedChats = Chat::findAll(['order_id' => $orderId]);
        LogService::success('OrderStatusService. linked chats found');
        $notifications = Notification::findAll([
            'entity_id' => $orderId,
            'entity_type' => Notification::ENTITY_TYPE_ORDER,
        ]);
        LogService::success('OrderStatusService. notifications found');

        $transaction = Yii::$app->db->beginTransaction();
        LogService::success('OrderStatusService. transaction started');
        try {
            LogService::success('OrderStatusService. Start marking notifications as read for managmeent');
            foreach ($notifications as $notification) {
                $notificationIsRead = NotificationManagementService::markAsRead(
                    $notification->id,
                );

                if (!$notificationIsRead->success) {
                    $transaction?->rollBack();

                    return $notificationIsRead;
                }
            }
            LogService::success('OrderStatusService. Notifications marked as read');
            LogService::success('OrderStatusService. Start archiving chats ');
            if ($linkedChats) {
                foreach ($linkedChats as $chat) {
                    $chatArchiveStatus = ChatArchiveService::archiveChat(
                        $chat->id,
                    );

                    if (!$chatArchiveStatus->success) {
                        $transaction?->rollBack();

                        return $chatArchiveStatus;
                    }
                }
            }
            LogService::success('OrderStatusService. Chats archived');
            $transaction?->commit();
            LogService::success('OrderStatusService. Start changing order status to completed');
            return self::changeOrderStatus(Order::STATUS_COMPLETED, $orderId);
        } catch (Throwable $e) {
            return Result::error(['errors' => ['base' => $e->getMessage()]]);
        }
    }

    public static function cancelled(int $orderId): ResultAnswer
    {
        LogService::info('OrderStatusService. Starting cancellation process for order id: ' . $orderId);
        $order = Order::find()
            ->select(['id', 'status', 'created_by'])
            ->where(['id' => $orderId])
            ->one();

        if (
            $order &&
            !in_array(
                $order->status,
                [
                    Order::STATUS_CREATED,
                    Order::STATUS_BUYER_ASSIGNED,
                    Order::STATUS_BUYER_OFFER_CREATED,
                    Order::STATUS_BUYER_OFFER_ACCEPTED,
                    Order::STATUS_BUYER_INSPECTION_COMPLETE,
                ],
                true,
            )
        ) {
            LogService::danger('OrderStatusService. Order in current status cannot be cancelled');
            return Result::error([
                'errors' => [
                    'status' => 'Order in current status cannot be cancelled',
                ],
            ]);
        }

        LogService::success('OrderStatusService. Order status is allowed for cancellation');
        $linkedChats = Chat::findAll(['order_id' => $orderId]);
        LogService::success('OrderStatusService. Linked chats found');
        $notifications = Notification::findAll([
            'entity_id' => $orderId,
            'entity_type' => Notification::ENTITY_TYPE_ORDER,
        ]);
        LogService::log('OrderStatusService. Notifications found');

        $transaction = Yii::$app->db->beginTransaction();
        LogService::log('OrderStatusService. Transaction started');

        try {
            if ($notifications) {
                LogService::log('OrderStatusService. Start marking notifications as read');
                foreach ($notifications as $notification) {
                    $notificationIsRead = NotificationManagementService::markAsRead(
                        $notification->id,
                    );

                    if (!$notificationIsRead->success) {
                        $transaction?->rollBack();
                        LogService::danger('OrderStatusService. Failed to mark notification as read');
                        return $notificationIsRead;
                    }
                }
                LogService::log('OrderStatusService. Notifications marked as read');
            }
            if ($linkedChats) {
                LogService::log('OrderStatusService. Start archiving chats');
                foreach ($linkedChats as $chat) {
                    $chatArchiveStatus = ChatArchiveService::archiveChat(
                        $chat->id,
                    );

                    if (!$chatArchiveStatus->success) {
                        $transaction?->rollBack();
                        LogService::danger('OrderStatusService. Failed to archive chat');
                        return $chatArchiveStatus;
                    }
                }
                LogService::log('OrderStatusService. Chats archived');
            }
            $transaction?->commit();
            LogService::log('OrderStatusService. Transaction committed');

            $orderStatus = in_array(
                $order->status,
                Order::STATUS_GROUP_ORDER,
                true,
            )
                ? Order::STATUS_CANCELLED_ORDER
                : Order::STATUS_CANCELLED_REQUEST;

            LogService::log('OrderStatusService. Changing order status to ' . $orderStatus);
            return self::changeOrderStatus($orderStatus, $orderId);
        } catch (Throwable $e) {
            $transaction?->rollBack();
            LogService::danger('OrderStatusService. Error during cancellation: ' . $e->getMessage());
            return Result::error(['errors' => ['base' => $e->getMessage()]]);
        }
    }

    private static function changeOrderStatus(
        string $orderStatus,
        int $orderId,
    ): ResultAnswer {
        try {
            $order = Order::findOne(['id' => $orderId]);

            if (!$order) {
                return Result::notFound();
            }
            LogService::log('OrderStatusService. order found for change status in ChangeOrderStatus method');
            $order->status = $orderStatus;

            if (!$order->save(true, ['status'])) {
                return Result::error(['errors' => $order->getFirstErrors()]);
            }
            LogService::log('order status changed');

            if (
                $order->status !== Order::STATUS_COMPLETED &&
                $order->status !== Order::STATUS_CANCELLED_REQUEST &&
                $order->status !== Order::STATUS_CANCELLED_ORDER
            ) {
                if ($order->status !== Order::STATUS_CREATED) {
                    NotificationConstructor::orderOrderStatusChange(
                        $order->created_by,
                        $orderId,
                    );
                    LogService::log('OrderStatusService. notification constructor for created by');
                    if ($order->buyer_id) {
                        NotificationConstructor::orderOrderStatusChange(
                            $order->buyer_id,
                            $orderId,
                        );
                    }
                    LogService::log('OrderStatusService. notification constructor for buyer id');
                    if ($order->manager_id) {
                        NotificationConstructor::orderOrderStatusChange(
                            $order->manager_id,
                            $orderId,
                        );
                    }
                    LogService::log('OrderStatusService. notification constructor for manager id');
                    if (
                        $order->fulfillment_id &&
                        in_array(
                            $order->status,
                            Order::STATUS_GROUP_ORDER,
                            true,
                        )
                    ) {
                        NotificationConstructor::orderOrderStatusChange(
                            $order->fulfillment_id,
                            $orderId,
                        );
                    }
                    LogService::log('OrderStatusService. notification constructor for fulfillment id');
                    if (
                        $order->status ===
                        Order::STATUS_PARTIALLY_DELIVERED_TO_MARKETPLACE
                    ) {
                        NotificationConstructor::orderOrderMarketplaceTransaction(
                            $order->created_by,
                            $orderId,
                        );
                        NotificationConstructor::orderOrderMarketplaceTransaction(
                            $order->manager_id,
                            $orderId,
                        );
                    }
                }

                if ($order->status === Order::STATUS_BUYER_OFFER_ACCEPTED) {
                    NotificationConstructor::orderOrderWaitingPayment(
                        $order->manager_id,
                        $orderId,
                    );
                    LogService::log('OrderStatusService. notification constructor for manager id');
                }
            } else {
                if ($order->manager_id) {
                    NotificationConstructor::orderOrderCompleted(
                        $order->manager_id,
                        $orderId,
                    );
                }

                if ($order->buyer_id) {
                    NotificationConstructor::orderOrderCompleted(
                        $order->buyer_id,
                        $orderId,
                    );
                }

                if ($order->fulfillment_id) {
                    NotificationConstructor::orderOrderCompleted(
                        $order->fulfillment_id,
                        $orderId,
                    );
                }

                NotificationConstructor::orderOrderCompleted(
                    $order->created_by,
                    $orderId,
                );
            }

            return Result::success();
        } catch (Throwable $e) {
            return Result::error(['errors' => ['base' => $e->getMessage()]]);
        }
    }
}
