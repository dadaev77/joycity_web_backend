<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\OrderDistribution;
use app\services\RateService;
use Yii;

class OrderDistributionOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = OrderDistribution::find()
            ->with([
                'order',
            ])
            ->where(['id' => $ids]);

        return array_map(static function ($model) {
            $info = ModelTypeHelper::toArray($model);
            $subcategory = $model->order->subcategory;
            $category = $subcategory->category;
            $appLang = Yii::$app->user->getIdentity()->settings->application_language;
            $userCurrency = Yii::$app->user->getIdentity()->settings->currency;
            $lang = match (strtolower($appLang)) {
                'ru' => 'ru_name',
                'en' => 'en_name',
                'zh' => 'zh_name',
                default => 'ru_name',
            };
            $info['order']['product_name'] = $category ? $category->{$lang} . ' / ' . $subcategory->{$lang} : $subcategory->{$lang};
            $info['order']['expected_price_per_item'] = RateService::convertValue($info['order']['expected_price_per_item'], $info['order']['currency'], $userCurrency);
            unset(
                $info['order_id'],
                $info['buyer_ids_list'],
                $info['order']['subcategory_id'],
                $info['order']['subcategory']['category_id'],
            );

            return $info;
        }, $query->all());
    }
}
