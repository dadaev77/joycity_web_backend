<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\Notification;

class NotificationOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = Notification::find()->where(['id' => $ids]);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            return $info;
        }, $query->all());
    }
}
