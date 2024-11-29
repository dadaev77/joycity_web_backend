<?php

namespace app\services\output;

use app\helpers\ModelTypeHelper;
use app\models\BuyerDeliveryOffer;
use app\services\RateService;
use Yii;

class BuyerDeliveryOfferOutputService extends OutputService
{
    public static function getEntity(int $id): array
    {
        return self::getCollection([$id])[0];
    }

    public static function getCollection(array $ids): array
    {
        $query = BuyerDeliveryOffer::find()->where(['id' => $ids]);

        $userCurrency = Yii::$app->getUser()->getIdentity()->getSettings()->currency;

        return array_map(static function ($model) use ($userCurrency) {
            $info = ModelTypeHelper::toArray($model);

            // Конвертация цен в валюту пользователя
            $info = RateService::convertDataPrices($info, ['price_delivery'], $info['currency'], $userCurrency);

            return $info;
        }, $query->all());
    }
}
