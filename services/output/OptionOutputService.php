<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\AppOption;
use app\services\OptionsService;

class OptionOutputService extends OptionsService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = AppOption::find()->where([
            'id' => $ids,
        ]);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            return $info;
        }, $query->all());
    }
}
