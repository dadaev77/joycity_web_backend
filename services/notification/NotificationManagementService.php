<?php

namespace app\services\notification;

use app\components\responseFunction\Result;
use app\components\responseFunction\ResultAnswer;
use app\models\Notification;

class NotificationManagementService
{
    public static function markAsRead(int $notificationId): ResultAnswer
    {
        $notification = Notification::findOne(['id' => $notificationId]);

        if (!$notification) {
            return Result::success();
        }

        $notification->is_read = 1;

        if (!$notification->save()) {
            return Result::errors($notification->getFirstErrors());
        }

        return Result::success();
    }
}
