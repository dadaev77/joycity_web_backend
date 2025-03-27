<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\ProductStockReport;

class ProductStockReportOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = ProductStockReport::find()
            ->where([
                'id' => $ids,
            ])
            ->with(['attachments']);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);
            $info['adsasd'] = 'asdasdasdasd';
            unset($info['productStockReportLinkAttachments']);

            return $info;
        }, $query->all());
    }
}
