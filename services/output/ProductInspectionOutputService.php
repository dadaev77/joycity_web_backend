<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\structure\ProductInspectionReportStructure;

class ProductInspectionOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = ProductInspectionReportStructure::find()->where([
            'id' => $ids,
        ]);

        return array_map(static function ($model) {
            return ModelTypeHelper::toArray($model);
        }, $query->all());
    }
}
