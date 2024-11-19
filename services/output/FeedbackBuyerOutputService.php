<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\FeedbackBuyer;
use app\services\SqlQueryService;

class FeedbackBuyerOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = FeedbackBuyer::find()
            ->with([
                'attachments',
                'createdBy' => fn($q) => $q
                    ->select(SqlQueryService::getUserSelect())
                    ->with(['avatar']),
                'buyer' => fn($q) => $q
                    ->select(SqlQueryService::getBuyerSelect())
                    ->with(['avatar']),
            ])
            ->orderBy(self::getOrderByIdExpression($ids))
            ->where(['id' => $ids]);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            unset(
                $info['feedbackBuyerLinkAttachments'],
                $info['created_by'],
                $info['buyer_id'],
            );

            return $info;
        }, $query->all());
    }
}
