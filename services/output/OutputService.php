<?php

namespace app\services\output;

use yii\db\Expression;

abstract class OutputService
{
    abstract public static function getEntity(int $id): array;
    abstract public static function getCollection(array $ids): array;

    protected static function getOrderByIdExpression(array $ids)
    {
        if (!$ids) {
            return [];
        }

        return [new Expression('FIELD(id, ' . implode(',', $ids) . ')')];
    }
}
