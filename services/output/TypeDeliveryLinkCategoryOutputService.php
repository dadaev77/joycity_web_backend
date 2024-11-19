<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\TypeDeliveryLinkCategory;

class TypeDeliveryLinkCategoryOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = TypeDeliveryLinkCategory::find()
            ->with(['category', 'typeDelivery'])
            ->where([
                'id' => $ids,
            ]);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            unset($info['category_id'], $info['type_delivery_id']);

            return $info;
        }, $query->all());
    }
}
