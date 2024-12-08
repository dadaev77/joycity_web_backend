<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\OrderDistribution;
use app\services\UserActionLogService as Log;

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
                    'category', // subcategory
                ]),
            ])
            ->where(['id' => $ids]);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            Log::info('OrderDistributionOutputService: ' . json_encode($info));

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
