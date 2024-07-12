<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\User;
use app\services\SqlQueryService;

class FulfillmentOutputService extends OutputService
{
    public static function getEntity(int $id, $showDeleted = false): array
    {
        return self::getCollection([$id], $showDeleted)[0];
    }

    public static function getCollection(
        array $ids,
        $showDeleted = false,
    ): array {
        $query = User::find()
            ->with(['avatar', 'deliveryPointAddress', 'userSettings'])
            ->where(['id' => $ids, 'role' => User::ROLE_FULFILLMENT])
            ->select(SqlQueryService::getBuyerSelect());

        if ($showDeleted) {
            $query->showWithDeleted();
        }

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            unset($info['avatar_id']);

            return $info;
        }, $query->all());
    }
}
