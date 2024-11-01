<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\User;
use app\services\SqlQueryService;

class ProfileOutputService extends OutputService
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
            ->with(['avatar'])
            ->select(SqlQueryService::getUserSelect())
            ->where(['id' => $ids]);

        if ($showDeleted) {
            $query->showWithDeleted();
        }

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            $info['telegram'] = User::find()->where(['id' => $model->id])->one()->telegram;
            unset($info['avatar_id']);

            return $info;
        }, $query->all());
    }
}
