<?php

namespace app\services\notification;

use app\components\responseFunction\Result;
use app\components\responseFunction\ResultAnswer;
use app\models\Notification;
use app\services\output\NotificationOutputService;
use app\services\WebsocketService;

class NotificationConstructor
{
    protected static function createNotification(
        Notification $notification,
        bool $publish = true,
    ): ResultAnswer {
        if (!$notification->user_id) {
            return Result::errors(['user_id' => 'User id is not set']);
        }

        if (!$notification->created_at) {
            $notification->created_at = date('Y-m-d H:i:s');
        }

        if (!$notification->save()) {
            return Result::notValid($notification->getFirstErrors());
        }

        if ($publish) {
            WebsocketService::sendNotification([$notification->user_id], NotificationOutputService::getEntity($notification->id), false);
        }

        return Result::success();
    }

    public static function orderOrderCreated(int $userId, int $orderId)
    {
        $notification = new Notification([
            'user_id' => $userId,
            'event' => Notification::EVENT_ORDER_CREATED,
            'entity_type' => Notification::ENTITY_TYPE_ORDER,
            'entity_id' => $orderId,
        ]);

        return self::createNotification($notification);
    }

    public static function orderOrderMarketplaceTransaction(
        int $userId,
        int $orderId,
    ) {
        $notification = new Notification([
            'user_id' => $userId,
            'event' => Notification::EVENT_MARKETPLACE_TRANSACTION,
            'entity_type' => Notification::ENTITY_TYPE_ORDER,
            'entity_id' => $orderId,
        ]);

        return self::createNotification($notification);
    }

    public static function orderOrderStatusChange(int $userId, int $orderId)
    {
        $notification = new Notification([
            'user_id' => $userId,
            'event' => Notification::EVENT_ORDER_STATUS_CHANGE,
            'entity_type' => Notification::ENTITY_TYPE_ORDER,
            'entity_id' => $orderId,
        ]);

        return self::createNotification($notification);
    }

    public static function orderOrderCompleted(int $userId, int $orderId)
    {
        $notification = new Notification([
            'user_id' => $userId,
            'event' => Notification::EVENT_ORDER_COMPLETED,
            'entity_type' => Notification::ENTITY_TYPE_ORDER,
            'entity_id' => $orderId,
        ]);

        return self::createNotification($notification);
    }

    public static function orderOrderWaitingPayment(int $userId, int $orderId)
    {
        $notification = new Notification([
            'user_id' => $userId,
            'event' => Notification::EVENT_ORDER_WAITING_PAYMENT,
            'entity_type' => Notification::ENTITY_TYPE_ORDER,
            'entity_id' => $orderId,
        ]);

        return self::createNotification($notification);
    }

    public static function verificationVerificationCreated(
        int $userId,
        int $verificationFormId,
    ) {
        $notification = new Notification([
            'user_id' => $userId,
            'event' => Notification::EVENT_VERIFICATION_CREATED,
            'entity_type' => Notification::ENTITY_TYPE_VERIFICATION,
            'entity_id' => $verificationFormId,
        ]);

        return self::createNotification($notification);
    }
}
