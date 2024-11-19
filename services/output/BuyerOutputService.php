<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\User;
use app\services\SqlQueryService;

class BuyerOutputService extends OutputService
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
            ->with([
                'avatar',
                'categories' => fn($q) => $q->with(['avatar']),
                'delivery',
            ])
            ->where(['id' => $ids, 'role' => User::ROLE_BUYER])
            ->select(SqlQueryService::getBuyerSelect());

        if ($showDeleted) {
            $query->showWithDeleted();
        }

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            $info['categories'] = array_map(static function ($category) {
                unset($category['avatar_id']);

                return $category;
            }, $info['categories']);

            unset(
                $info['avatar_id'],
                $info['userLinkCategories'],
                $info['userLinkTypeDeliveries'],
            );

            return $info;
        }, $query->all());
    }
}
