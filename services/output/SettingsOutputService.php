<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\User;

class SettingsOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = User::find()
            ->with([
                'categories',
                'userSettings',
                'packaging',
                'delivery',
                'deliveryPointAddress',
            ])
            ->select(['id', 'role'])
            ->where(['id' => $ids]);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            if ($info['role'] === User::ROLE_CLIENT) {
                unset($info['packaging'], $info['delivery']);
            }

            if ($info['role'] === User::ROLE_FULFILLMENT) {
                unset(
                    $info['packaging'],
                    $info['delivery'],
                    $info['categories'],
                    $info['userSettings']['use_only_selected_categories'],
                );
            }

            $info['telegram'] = $model;


            if ($info['role'] !== User::ROLE_FULFILLMENT) {
                unset($info['deliveryPointAddress']);
            }

            unset(
                $info['role'],
                $info['userLinkCategories'],
                $info['userLinkTypePackagings'],
                $info['userLinkTypeDeliveries'],
            );

            return $info;
        }, $query->all());
    }
}
