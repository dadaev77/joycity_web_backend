<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\FeedbackProduct;
use app\services\SqlQueryService;

class FeedbackProductOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = FeedbackProduct::find()
            ->with([
                'attachments',
                'createdBy' => fn($q) => $q
                    ->select(SqlQueryService::getUserSelect())
                    ->with(['avatar']),
                'product' => function ($q) {
                    $q->select(['id', 'name', 'rating', 'feedback_count']);
                },
            ])
            ->orderBy(self::getOrderByIdExpression($ids))
            ->where(['id' => $ids]);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);

            unset($info['feedbackProductLinkAttachments'], $info['created_by']);

            return $info;
        }, $query->all());
    }
}
