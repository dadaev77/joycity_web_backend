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
    public const SYMBOLS_AFTER_DECIMAL_POINT = 2;
    public const BASE_CURRENCY = 'CNY';
    public const DEFAULT_VOLUME_PRICE = 350.0; // Цена за м³ для низкой плотности
    public const DENSITY_THRESHOLD = 100.0; // Порог плотности (кг/м³)

    /**
     * Возвращает конфигурацию цен для типа доставки
     *
     * @param int $typeDeliveryId ID типа доставки
     * @return array
     */
    public static function typeDeliveryPriceConfig(int $typeDeliveryId): array
    {
        try {
            $typeDeliveryPrices = TypeDeliveryPrice::find()
                ->where(['type_delivery_id' => $typeDeliveryId])
                ->all();
            return $typeDeliveryPrices;
        } catch (Throwable $e) {
            Yii::error("Ошибка получения конфигурации цен доставки #$typeDeliveryId: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Добавляет диапазоны цен для типа доставки
     *
     * @param int $typeDeliveryId ID типа доставки
     * @return ResultAnswer
     */
    public static function addPriceRangeToTypeDelivery(int $typeDeliveryId): ResultAnswer
    {
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
                    $transaction->rollBack();
                    return Result::errors($typeDeliveryPrice->getFirstErrors());
                }
            }

            $transaction->commit();
            return Result::success();
        } catch (Throwable $e) {
            if (isset($transaction)) {
                $transaction->rollBack();
            }
            Yii::error("Ошибка добавления диапазонов цен для доставки #$typeDeliveryId: {$e->getMessage()}");
            return Result::error();
        }
    }

    /**
     * Возвращает цену доставки по весу
     *
     * @param int $typeDeliveryId ID типа доставки
     * @param float $density Плотность груза (кг/м³)
     * @return float
     */
    public static function getPriceByWeight(int $typeDeliveryId, float $density): float
    {
        try {
            $typeDeliveryPrice = TypeDeliveryPrice::find()
                ->where(['type_delivery_id' => $typeDeliveryId])
                ->andWhere([
                    'AND',
                    ['<=', 'range_min', $density],
                    ['>', 'range_max', $density],
                ])
                ->one();

            return $typeDeliveryPrice?->price ?? 0.0;
        } catch (Throwable $e) {
            Yii::error("Ошибка получения цены по весу для доставки #$typeDeliveryId: {$e->getMessage()}");
            return 0.0;
        }
    }

    /**
     * Рассчитывает плотность продукта
     *
     * @param float $widthPerItem Ширина (см)
     * @param float $heightPerItem Высота (см)
     * @param float $depthPerItem Глубина (см)
     * @param float $weightPerItem Вес (г)
     * @return float Плотность (кг/м³)
     */
    public static function calculateProductDensity(
        float $widthPerItem,
        float $heightPerItem,
        float $depthPerItem,
        float $weightPerItem
    ): float {
        if (!$widthPerItem || !$heightPerItem || !$depthPerItem || !$weightPerItem) {
            return 0.0;
        }

        $volumeCm3 = $widthPerItem * $heightPerItem * $depthPerItem; // Объём в см³
        $volumeM3 = $volumeCm3 / 1_000_000; // Объём в м³
        $weightKg = $weightPerItem / 1000; // Вес в кг
        return $volumeM3 ? $weightKg / $volumeM3 : 0.0;
    }

    /**
     * Рассчитывает цену доставки
     *
     * @param int $itemsCount Количество единиц
     * @param float $widthPerItem Ширина единицы (см)
     * @param float $heightPerItem Высота единицы (см)
     * @param float $depthPerItem Глубина единицы (см)
     * @param float $weightPerItem Вес единицы (г)
     * @param int $typeDeliveryId ID типа доставки
     * @return float Цена доставки в базовой валюте
     */
    public static function calculateDeliveryPrice(
        int $itemsCount,
        float $widthPerItem,
        float $heightPerItem,
        float $depthPerItem,
        float $weightPerItem,
        int $typeDeliveryId
    ): float {
        try {
            $density = self::calculateProductDensity(
                $widthPerItem,
                $heightPerItem,
                $depthPerItem,
                $weightPerItem
            );

            $volumeCm3 = $widthPerItem * $heightPerItem * $depthPerItem; // Объём в см³
            $volumeM3 = $volumeCm3 / 1_000_000; // Объём в м³
            $weightPerItemKg = $weightPerItem / 1000; // Вес в кг

            $deliveryPrice = 0.0;

            if ($density > self::DENSITY_THRESHOLD) {
                $totalWeight = $itemsCount * $weightPerItemKg;
                $densityPrice = self::getPriceByWeight($typeDeliveryId, $density);
                $deliveryPrice = $densityPrice * $totalWeight;
            } else {
                $deliveryPrice = ($volumeM3 * $itemsCount) * self::getPriceByVolume($typeDeliveryId);
            }

            return round($deliveryPrice, self::SYMBOLS_AFTER_DECIMAL_POINT);
        } catch (Throwable $e) {
            Yii::error("Ошибка расчёта цены доставки для типа #$typeDeliveryId: {$e->getMessage()}");
            return 0.0;
        }
    }

    /**
     * Возвращает цену доставки по объёму
     *
     * @param int $typeDeliveryId ID типа доставки
     * @return float
     */
    private static function getPriceByVolume(int $typeDeliveryId): float
    {
        try {
            $typeDeliveryPrice = TypeDeliveryPrice::find()
                ->where(['type_delivery_id' => $typeDeliveryId])
                ->one();

            return $typeDeliveryPrice?->price ?? self::DEFAULT_VOLUME_PRICE;
        } catch (Throwable $e) {
            Yii::error("Ошибка получения цены по объёму для доставки #$typeDeliveryId: {$e->getMessage()}");
            return self::DEFAULT_VOLUME_PRICE;
        }
    }

    /**
     * Рассчитывает цену упаковки
     *
     * @param int $typePackagingId ID типа упаковки
     * @param int $packagingQuantity Количество упаковок
     * @return float Цена в базовой валюте
     */
    public static function calculatePackagingPrice(
        int $typePackagingId,
        int $packagingQuantity
    ): float {
        try {
            $typePackaging = TypePackaging::findOne(['id' => $typePackagingId]);
            return round(
                ($typePackaging?->price ?? 0.0) * $packagingQuantity,
                self::SYMBOLS_AFTER_DECIMAL_POINT
            );
        } catch (Throwable $e) {
            Yii::error("Ошибка расчёта цены упаковки для типа #$typePackagingId: {$e->getMessage()}");
            return 0.0;
        }
    }
}
