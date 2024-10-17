<?php

namespace app\services\price;

use app\components\responseFunction\Result;
use app\components\responseFunction\ResultAnswer;
use app\models\TypeDeliveryPrice;
use app\models\TypePackaging;
use Throwable;
use Yii;

class OrderDeliveryPriceService extends PriceOutputService
{
    public static function addPriceRangeToTypeDelivery(
        int $typeDeliveryId,
    ): ResultAnswer {
        try {
            $rangeConfig = [
                ['from' => 0, 'to' => 50],
                ['from' => 50, 'to' => 80],
                ['from' => 80, 'to' => 100],
                ['from' => 100, 'to' => 110],
                ['from' => 110, 'to' => 120],
                ['from' => 120, 'to' => 130],
                ['from' => 130, 'to' => 140],
                ['from' => 140, 'to' => 150],
                ['from' => 150, 'to' => 160],
                ['from' => 160, 'to' => 170],
                ['from' => 170, 'to' => 180],
                ['from' => 180, 'to' => 190],
                ['from' => 190, 'to' => 200],
                ['from' => 200, 'to' => 250],
                ['from' => 250, 'to' => 300],
                ['from' => 300, 'to' => 350],
                ['from' => 350, 'to' => 400],
                ['from' => 400, 'to' => 500],
                ['from' => 500, 'to' => 600],
                ['from' => 600, 'to' => 700],
                ['from' => 700, 'to' => 800],
                ['from' => 800, 'to' => 900],
                ['from' => 900, 'to' => 1000],
                ['from' => 1000, 'to' => 1e6],
            ];

            $transaction = Yii::$app->db->beginTransaction();

            foreach ($rangeConfig as $config) {
                $typeDeliveryPrice = new TypeDeliveryPrice([
                    'type_delivery_id' => $typeDeliveryId,
                    'range_min' => $config['from'],
                    'range_max' => $config['to'],
                    'price' => 0,
                ]);

                if (!$typeDeliveryPrice->save()) {
                    $transaction?->rollBack();

                    return Result::errors($typeDeliveryPrice->getFirstErrors());
                }
            }

            $transaction?->commit();

            return Result::success();
        } catch (Throwable $e) {
            isset($transaction) && $transaction->rollBack();

            return Result::error();
        }
    }

    public static function getPriceByWeight(
        int $typeDeliveryId,
        float $weight,
    ): float {
        $typeDeliveryPrice = TypeDeliveryPrice::find()
            ->where(['type_delivery_id' => $typeDeliveryId])
            ->andWhere([
                'AND',
                ['<=', 'range_min', $weight],
                ['>', 'range_max', $weight],
            ])
            ->one();

        if (!$typeDeliveryPrice) {
            return 0;
        }

        return $typeDeliveryPrice->price;
    }

    public static function calculateProductDensity(
        float $widthPerItem,
        float $heightPerItem,
        float $depthPerItem,
        float $weightPerItem,
    ): float {
        if (
            !$weightPerItem ||
            !$widthPerItem ||
            !$heightPerItem ||
            !$depthPerItem
        ) {
            return 0;
        }

        return $weightPerItem /
            ($widthPerItem * $heightPerItem * $depthPerItem);
    }

    public static function calculateDeliveryPrice(
        int $itemsCount,
        float $widthPerItem,
        float $heightPerItem,
        float $depthPerItem,
        float $weightPerItem,
        int $typeDeliveryId,
    ): float {
        $density = self::calculateProductDensity(
            $widthPerItem,
            $heightPerItem,
            $depthPerItem,
            $weightPerItem,
        );

        // $densityPrice = self::getPriceByWeight($typeDeliveryId, $density);

        //new logic
        $volumeM2 = $widthPerItem * $heightPerItem * $depthPerItem;
        $volumeM3 = $volumeM2 / 1000000;
        $weightPerItemKg = $weightPerItem / 1000;
        $density = $weightPerItemKg / $volumeM3;

        $deliveryPrice = 0;

        if ($density > 100) {

            $totalWeight = ($itemsCount * $weightPerItemKg) + $packagingWeight;
            $densityPrice = self::getPriceByWeight($typeDeliveryId, $density);
            $deliveryPrice = $densityPrice * $totalWeight;
        } else {
            $deliveryPrice = ($volumeM3 * $itemsCount) * self::getPriceByVolume($typeDeliveryId);
        }

        return round($deliveryPrice, self::SYMBOLS_AFTER_DECIMAL_POINT);
    }

    private static function getPriceByVolume(int $typeDeliveryId): float
    {
        return 350;
    }

    public static function calculatePackagingPrice(
        int $typePackagingId,
        int $packagingQuantity,
    ): float {
        try {
            $typePackaging = TypePackaging::findOne([
                'id' => $typePackagingId,
            ]);

            return round(($typePackaging?->price ?: 0) * $packagingQuantity, self::SYMBOLS_AFTER_DECIMAL_POINT);
        } catch (Throwable) {
            return 0;
        }
    }
}
