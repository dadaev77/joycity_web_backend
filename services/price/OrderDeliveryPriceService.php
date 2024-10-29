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

    private static function typeDeliveryPriceConfig(int $typeDeliveryId): array
    {
        return [
            8 => [
                [
                    'id' => 1,
                    'density_from' => 1000,
                    'density_to' => 1000,
                    'price' => 2.00,
                ],
                [
                    'id' => 2,
                    'density_from' => 900,
                    'density_to' => 1000,
                    'price' => 2.00,
                ],
                [
                    'id' => 3,
                    'density_from' => 800,
                    'density_to' => 900,
                    'price' => 2.00,
                ],
                [
                    'id' => 4,
                    'density_from' => 700,
                    'density_to' => 800,
                    'price' => 2.00,
                ],
                [
                    'id' => 5,
                    'density_from' => 600,
                    'density_to' => 700,
                    'price' => 2.10,
                ],
                [
                    'id' => 6,
                    'density_from' => 500,
                    'density_to' => 600,
                    'price' => 2.15,
                ],
                [
                    'id' => 7,
                    'density_from' => 400,
                    'density_to' => 500,
                    'price' => 2.20,
                ],
                [
                    'id' => 8,
                    'density_from' => 350,
                    'density_to' => 400,
                    'price' => 2.25,
                ],
                [
                    'id' => 9,
                    'density_from' => 300,
                    'density_to' => 350,
                    'price' => 2.30,
                ],
                [
                    'id' => 10,
                    'density_from' => 250,
                    'density_to' => 300,
                    'price' => 2.40,
                ],
                [
                    'id' => 11,
                    'density_from' => 200,
                    'density_to' => 250,
                    'price' => 2.45,
                ],
                [
                    'id' => 12,
                    'density_from' => 190,
                    'density_to' => 200,
                    'price' => 2.60,
                ],
                [
                    'id' => 13,
                    'density_from' => 180,
                    'density_to' => 190,
                    'price' => 2.70,
                ],
                [
                    'id' => 14,
                    'density_from' => 170,
                    'density_to' => 180,
                    'price' => 2.80,
                ],
                [
                    'id' => 15,
                    'density_from' => 160,
                    'density_to' => 170,
                    'price' => 2.90,
                ],
                [
                    'id' => 16,
                    'density_from' => 150,
                    'density_to' => 160,
                    'price' => 3.00,
                ],
                [
                    'id' => 17,
                    'density_from' => 140,
                    'density_to' => 150,
                    'price' => 3.10,
                ],
                [
                    'id' => 18,
                    'density_from' => 130,
                    'density_to' => 140,
                    'price' => 3.20,
                ],
                [
                    'id' => 19,
                    'density_from' => 120,
                    'density_to' => 130,
                    'price' => 3.30,
                ],
                [
                    'id' => 20,
                    'density_from' => 110,
                    'density_to' => 120,
                    'price' => 3.40,
                ],
                [
                    'id' => 21,
                    'density_from' => 100,
                    'density_to' => 110,
                    'price' => 3.50,
                ],
                [
                    'id' => 22,
                    'density_from' => 80,
                    'density_to' => 100,
                    'price' => 350, // Price per m³
                ],
                [
                    'id' => 23,
                    'density_from' => 50,
                    'density_to' => 80,
                    'price' => 330, // Price per m³
                ],
                [
                    'id' => 24,
                    'density_from' => 50,
                    'density_to' => 1,
                    'price' => 310, // Price per m³
                ],
            ],
            9 => [  // fast car
                [
                    'id' => 1,
                    'density_from' => 800,
                    'density_to' => 800,
                    'price' => 2.75,
                ],
                [
                    'id' => 2,
                    'density_from' => 600,
                    'density_to' => 800,
                    'price' => 2.80,
                ],
                [
                    'id' => 3,
                    'density_from' => 500,
                    'density_to' => 600,
                    'price' => 2.85,
                ],
                [
                    'id' => 4,
                    'density_from' => 400,
                    'density_to' => 500,
                    'price' => 2.90,
                ],
                [
                    'id' => 5,
                    'density_from' => 350,
                    'density_to' => 400,
                    'price' => 2.95,
                ],
                [
                    'id' => 6,
                    'density_from' => 300,
                    'density_to' => 350,
                    'price' => 3.00,
                ],
                [
                    'id' => 7,
                    'density_from' => 250,
                    'density_to' => 300,
                    'price' => 3.10,
                ],
                [
                    'id' => 8,
                    'density_from' => 200,
                    'density_to' => 250,
                    'price' => 3.20,
                ],
                [
                    'id' => 9,
                    'density_from' => 190,
                    'density_to' => 200,
                    'price' => 3.30,
                ],
                [
                    'id' => 10,
                    'density_from' => 180,
                    'density_to' => 190,
                    'price' => 3.40,
                ],
                [
                    'id' => 11,
                    'density_from' => 170,
                    'density_to' => 180,
                    'price' => 3.50,
                ],
                [
                    'id' => 12,
                    'density_from' => 160,
                    'density_to' => 170,
                    'price' => 3.60,
                ],
                [
                    'id' => 13,
                    'density_from' => 150,
                    'density_to' => 160,
                    'price' => 3.70,
                ],
                [
                    'id' => 14,
                    'density_from' => 140,
                    'density_to' => 150,
                    'price' => 3.80,
                ],
                [
                    'id' => 15,
                    'density_from' => 130,
                    'density_to' => 140,
                    'price' => 3.90,
                ],
                [
                    'id' => 16,
                    'density_from' => 120,
                    'density_to' => 130,
                    'price' => 4.00,
                ],
                [
                    'id' => 17,
                    'density_from' => 110,
                    'density_to' => 120,
                    'price' => 4.10,
                ],
                [
                    'id' => 18,
                    'density_from' => 100,
                    'density_to' => 110,
                    'price' => 4.20,
                ],
                [
                    'id' => 19,
                    'density_from' => 80,
                    'density_to' => 100,
                    'price' => 410, // Price per m³
                ],
                [
                    'id' => 20,
                    'density_from' => 50,
                    'density_to' => 80,
                    'price' => 390, // Price per m³
                ],
                [
                    'id' => 21,
                    'density_from' => 50,
                    'density_to' => 1,
                    'price' => 370, // Price per m³
                ]
            ]
        ];
    }

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
        bool $debug = false,
        int $orderId, // TODO: remove after testing
        int $itemsCount,
        float $widthPerItem,
        float $heightPerItem,
        float $depthPerItem,
        float $weightPerItem,
        int $typeDeliveryId
    ): float {
        \app\services\UserActionLogService::setController('OrderDeliveryPriceService');
        \app\services\UserActionLogService::log('call calculateDeliveryPrice');

        \app\services\UserActionLogService::log([
            'debug' => $debug,
            'order_id' => $orderId,
            'items_count' => $itemsCount,
            'width_per_item' => $widthPerItem,
            'height_per_item' => $heightPerItem,
            'depth_per_item' => $depthPerItem,
            'weight_per_item' => $weightPerItem,
            'type_delivery_id' => $typeDeliveryId,
        ]);

        /*
        * Логика расчета цены доставки
        * При запросе цены доставки, сначала определяем категорию товара,
        * затем последовательно поднимаемся по иерархии категорий, пока не найдем ID типа доставки (первый найденный)
        * Вес и размеры единицы товара нам интересны для определения плотности груза
        * Используем граммы и сантиметры, плотность груза получится в кг/м3
        * Если плотность груза больше 100, то считаем по плотности, иначе по объему
        * Вычисляем цену доставки с учетом категории и иерархии категорий
        * Модели: Order, Category, TypeDeliveryPrice
        * --------------------------------
        */

        /*
        * Определяем категорию товара
        */
        $parentsTree = [];
        $order = \app\models\Order::findOne($orderId);
        $category = \app\models\Category::findOne($order->subcategory_id);

        while ($category->parent_id) {
            $category = \app\models\Category::findOne($category->parent_id);
            $parentsTree[] = $category->id;
        }
        array_reverse($parentsTree);

        $typeDeliveryIds = [];
        foreach ($parentsTree as $parentId) {
            $typeDeliveryIds = \app\services\TypeDeliveryService::getTypeDeliveryIdsBySubcategory($parentId);
            if ($typeDeliveryIds) {
                $typeDeliveryIds = $typeDeliveryIds;
                break;
            }
        }

        /*
        * Переводим размеры и вес в сантиметры и граммы
        * --------------------------------
        */
        $volumeCm3 = $widthPerItem * $heightPerItem * $depthPerItem; // Объем в см³
        $volumeM3 = $volumeCm3 / 1000000; // Объем в м³
        $weightPerItemKg = $weightPerItem / 1000; // Вес в кг
        $density = $weightPerItemKg / $volumeM3; // Плотность в кг/м³

        $deliveryPrice = 0;

        if ($density > 100) {
            // Находим вес груза
            $totalWeight = $itemsCount * $weightPerItemKg; // Убираем упаковку
            $densityPrice = self::getPriceByWeight($typeDeliveryId, $density);
            $deliveryPrice = $densityPrice * $totalWeight; // Стоимость доставки в $
        } else {
            // Стоимость доставки в $ для плотности < 100
            $deliveryPrice = ($volumeM3 * $itemsCount) * self::getPriceByVolume($typeDeliveryId);
        }

        /*
        * Логируем данные для отладки из RawController
        */
        // if ($debug) {
        //     return [
        //         "Входные данные в м/кг" => [
        //             "ширина единицы" => $widthPerItem / 100 . ' м',
        //             "высота единицы" => $heightPerItem / 100 . ' м',
        //             "объём единицы" => $depthPerItem / 100 . ' м',
        //             "вес единицы" => $weightPerItem / 1000 . ' кг',
        //         ],
        //         "Плотность" => [
        //             'плотность' => $density,
        //             'цена за кг' => $densityPrice . ' $' ?? 'Недостаточно данных',
        //             'вес груза' => $totalWeight . ' кг' ?? 'Недостаточно данных',
        //         ],
        //         "Цена доставки $" => round($deliveryPrice, 2),
        //         "Цена доставки в рублях" => \app\services\RateService::convertUSDtoRUB($deliveryPrice),
        //         "ID типа доставки" => $typeDeliveryId,
        //         "количество товаров" => $itemsCount,
        //     ];
        // }

        /**
         * Конвертация валюты в рубли
         * --------------------------------
         */
        // RateService::convertUSDtoRUB($deliveryPrice);
        // $deliveryPriceRub = $deliveryPrice * \app\services\CurrencyService::getCurrencyRate();


        /*
        * Возвращаем стоимость доставки в $
        */
        return \app\services\RateService::convertUSDtoRUB($deliveryPrice); // Возвращаем стоимость в долларах
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
            \app\services\UserActionLogService::log('call calculate packaging price');
            $typePackaging = TypePackaging::findOne([
                'id' => $typePackagingId,
            ]);

            return round(($typePackaging?->price ?: 0) * $packagingQuantity, self::SYMBOLS_AFTER_DECIMAL_POINT);
        } catch (Throwable) {
            return 0;
        }
    }
}
