<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\Product;
use app\services\RateService;
use app\services\SqlQueryService;

class ProductOutputService extends OutputService
{
    public static function getEntity(int $id, string $imageSize = 'large'): array
    {
        return self::getCollection([$id], $imageSize)[0];
    }

    public static function getCollection(array $ids, string $imageSize = 'large'): array
    {
        $query = Product::find()
            ->with([
                'buyer' => fn($q) => $q
                    ->select(SqlQueryService::getBuyerSelect())
                    ->with(['avatar']),
                'subcategory' => fn($q) => $q->with(['category']),
            ])
            ->orderBy(self::getOrderByIdExpression($ids))
            ->where(['id' => $ids]);

        return array_map(static function ($model) use ($imageSize) {
            $info = ModelTypeHelper::toArray($model);

            foreach ($info as $key => $value) {
                if ($value && str_ends_with($key, '_price')) {
                    $info[$key] = RateService::outputInUserCurrency($value);
                }
            }

            $info['attachments'] = match ($imageSize) {
                'small' => $model->getAttachmentsSmallSize,
                'medium' => $model->getAttachmentsMediumSize,
                'large' => $model->getAttachmentsLargeSize,
                default => $model->attachments,
            };

            $info['price'] = [
                'min' => min(
                    $info['range_1_price'],
                    $info['range_2_price'] ?? $info['range_1_price'],
                    $info['range_3_price'] ?? $info['range_1_price'],
                    $info['range_4_price'] ?? $info['range_1_price'],
                ),
                'max' => max(
                    $info['range_1_price'],
                    $info['range_2_price'] ?? $info['range_1_price'],
                    $info['range_3_price'] ?? $info['range_1_price'],
                    $info['range_4_price'] ?? $info['range_1_price'],
                ),
            ];

            unset($info['productLinkAttachments']);

            return $info;
        }, $query->all());
    }
}
