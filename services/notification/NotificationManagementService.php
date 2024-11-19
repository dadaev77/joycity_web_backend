<?php

namespace app\services\notification;

use app\components\responseFunction\Result;
use app\components\responseFunction\ResultAnswer;
use app\models\Notification;
use app\services\UserActionLogService as LogService;

class NotificationManagementService
{
    public function init()
    {
        LogService::setController('NotificationManagementService');
    }
    public static function markAsRead(int $notificationId): ResultAnswer
    {
        $notification = Notification::findOne(['id' => $notificationId]);
        LogService::log('Notification found by id ' . $notificationId);
        if (!$notification) {
            LogService::log('Notification not found by id ' . $notificationId);
            return Result::success();
        }
        LogService::log('Notification found by id ' . $notificationId);
        $notification->is_read = 1;
        LogService::log('Notification is_read set to 1');

        if (!$notification->save()) {
            LogService::log('Notification not saved');
            return Result::errors($notification->getFirstErrors());
        }
        LogService::log('Notification saved');

        return Result::success();
    }
}
