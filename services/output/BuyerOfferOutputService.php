<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\BuyerOffer;
use app\services\RateService;
use Yii;
use app\services\UserActionLogService as Log;

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
            Log::info(' BuyerOfferOutputService::getCollection', json_encode($info));
            $info = RateService::convertDataPrices($info, ['price_product', 'price_inspection'], $info['currency'], $userCurrency);
            return $info;
        }, $query->all());
    }
}
