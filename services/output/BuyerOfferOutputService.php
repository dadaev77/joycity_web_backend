<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\BuyerOffer;
use app\services\RateService;
use Yii;

class BuyerOfferOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = BuyerOffer::find()->where(['id' => $ids]);
        $userCurrency = Yii::$app->user->getIdentity()->settings->currency;
        return array_map(static function ($model) use ($userCurrency) {
            $info = ModelTypeHelper::toArray($model);
            $info['price_product'] = RateService::convertValue($info['price_product'], $info['currency'], $userCurrency);
            $info['price_inspection'] = RateService::convertValue($info['price_inspection'], $info['currency'], $userCurrency);
            return $info;
        }, $query->all());
    }
}
