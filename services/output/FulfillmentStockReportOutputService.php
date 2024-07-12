<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\FulfillmentStockReport;

class FulfillmentStockReportOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = FulfillmentStockReport::find()
            ->where([
                'id' => $ids,
            ])
            ->with(['attachments']);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            unset($info['fulfillmentStockReportLinkAttachments']);

            return $info;
        }, $query->all());
    }
}
