<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\OrderDistribution;

class OrderDistributionOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = OrderDistribution::find()
            ->with([
                'order' => fn($q) => $q->with([
                    'subcategory' => fn($q) => $q->with(['category']),
                ]),
            ])
            ->where(['id' => $ids]);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            unset(
                $info['order_id'],
                $info['buyer_ids_list'],
                $info['order']['subcategory_id'],
                $info['order']['subcategory']['category_id'],
            );

            return $info;
        }, $query->all());
    }
}
