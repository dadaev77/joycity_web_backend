<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\DeliveryPointAddress;

class DeliveryPointAddressOutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = DeliveryPointAddress::find()->where([
            'id' => $ids,
        ]);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            unset($info['is_deleted']);

            return $info;
        }, $query->all());
    }
}
