<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\ChatTranslate;

class ChatTranslateOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = ChatTranslate::find()->where([
            'id' => $ids,
        ]);

        return array_map(static function ($model) {
            return ModelTypeHelper::toArray($model);
        }, $query->all());
    }
}
