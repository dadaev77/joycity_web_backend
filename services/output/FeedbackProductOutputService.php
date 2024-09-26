<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\FeedbackProduct;
use app\services\SqlQueryService;

class FeedbackProductOutputService extends OutputService
{
    public static function getEntity(int $id, string $imageSize = 'small'): array
    {
        return self::getCollection([$id], $imageSize)[0];
    }

    public static function getCollection(array $ids, string $imageSize = 'small'): array
    {
        $query = FeedbackProduct::find()
            ->with([
                'createdBy' => fn($q) => $q
                    ->select(SqlQueryService::getUserSelect())
                    ->with(['avatar']),
                'product' => function ($q) {
                    $q->select(['id', 'name', 'rating', 'feedback_count']);
                },
            ])
            ->orderBy(self::getOrderByIdExpression($ids))
            ->where(['id' => $ids]);

        return array_map(static function ($model) use ($imageSize) {
            $info = ModelTypeHelper::toArray($model);

            $info['attachments'] = match ($imageSize) {
                'small' => $model->attachmentsSmallSize,
                'medium' => $model->attachmentsMediumSize,
                'large' => $model->attachmentsLargeSize,
            };

            unset($info['feedbackProductLinkAttachments'], $info['created_by']);

            return $info;
        }, $query->all());
    }
}
