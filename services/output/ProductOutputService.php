<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\Product;
use app\services\RateService;
use app\services\SqlQueryService;
use Yii;

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


            $language = Yii::$app->user->identity->getSettings()->application_language;

            $keys = [
                'name_ru',
                'description_ru',
                'name_en',
                'description_en',
                'name_zh',
                'description_zh',
            ];

            foreach ($keys as $key) {
                unset($info[$key]);
            }

            $info['name'] = match (strtolower($language)) {
                'ru' => $model->name_ru,
                'en' => $model->name_en,
                'zh' => $model->name_zh,
                default => $model->name_ru,
            };

            $info['description'] = match (strtolower($language)) {
                'ru' => $model->description_ru,
                'en' => $model->description_en,
                'zh' => $model->description_zh,
                default => $model->description_ru,
            };

            $info['attachments'] = match ($imageSize) {
                'small' => $model->attachmentsSmallSize,
                'medium' => $model->attachmentsMediumSize,
                'large' => $model->attachmentsLargeSize,
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
