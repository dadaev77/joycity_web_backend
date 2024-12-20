<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\Category;
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
                'subcategory', // subcategory
            ])
            ->orderBy(self::getOrderByIdExpression($ids))
            ->where(['id' => $ids]);

        $userCurrency = Yii::$app->user->identity->getSettings()->currency;

        return array_map(static function ($model) use ($imageSize, $userCurrency) {
            $info = ModelTypeHelper::toArray($model);

            $language = Yii::$app->user->identity->getSettings()->application_language;

            if ($model->subcategory->parent_id) {
                $info['subcategory']['category'] = Category::find()->where(['id' => $model->subcategory->parent_id])->one();
            }


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
                'min' => min(array_filter([
                    $info['range_1_price'],
                    $info['range_2_price'] ?? $info['range_1_price'],
                    $info['range_3_price'] ?? $info['range_1_price'],
                    $info['range_4_price'] ?? $info['range_1_price'],
                ], fn($price) => $price > 0)),
                'max' => max(
                    $info['range_1_price'],
                    $info['range_2_price'] ?? $info['range_1_price'],
                    $info['range_3_price'] ?? $info['range_1_price'],
                    $info['range_4_price'] ?? $info['range_1_price'],
                ),
            ];

            $priceKeys = array_filter(array_keys($info), fn($key) => str_ends_with($key, '_price'));

            foreach ($priceKeys as $key) {
                $info[$key] = $info[$key];
            }

            $info['price']['min'] = $info['price']['min'];
            $info['price']['max'] = $info['price']['max'];

            unset(
                $info['productLinkAttachments'],
                // TODO: remove after testing
                // $info['feedback_count'],
                // $info['rating'],
                // $info['buyer_id'],
                // $info['subcategory_id'],
                // $info['is_deleted'],
                // $info['subcategory'],
                // $info['buyer'],
                // $info['attachments'],
            );

            // Конвертация цен в валюту пользователя
            $info = RateService::convertDataPrices($info, ['price', 'range_1_price', 'range_2_price', 'range_3_price', 'range_4_price'], $info['currency'], $userCurrency);

            return $info;
        }, $query->all());
    }
}
