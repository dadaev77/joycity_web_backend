<?php

namespace app\services;

use app\components\responseFunction\Result;
use app\models\TypeDeliveryPoint;
use Throwable;

class CustomFieldsService
{
    private static array $config = [
        [
            'class' => TypeDeliveryPoint::class,
            'fields' => ['id', 'en_name', 'ru_name', 'zh_name'],
            'values' => [
                [
                    TypeDeliveryPoint::TYPE_WAREHOUSE,
                    'Warehouse',
                    'Склад',
                    '仓库',
                ],
                [
                    TypeDeliveryPoint::TYPE_FULFILLMENT,
                    'Fulfilment',
                    'Фулфилмент',
                    '履行职责',
                ],
            ],
        ],
    ];

    public static function deployStaticFields()
    {
        $insertedCount = 0;
        $failedCount = 0;

        foreach (self::$config as $tableConfig) {
            $tableName = $tableConfig['class']::tableName();

            foreach ($tableConfig['values'] as $index => $preset) {
                try {
                    $model = $tableConfig['class']::findOne([$preset[0]]);

                    if (!$model) {
                        $model = new ($tableConfig['class'])();
                    }

                    $model->load(
                        array_combine($tableConfig['fields'], $preset),
                        '',
                    );

                    if (
                        !array_diff_assoc(
                            $model->getAttributes(),
                            $model->getOldAttributes(),
                        )
                    ) {
                        continue;
                    }

                    if ($model->save()) {
                        $insertedCount++;
                    } else {
                        $failedCount++;
                        echo "Failed to deploy custom field table: $tableName, index: $index" .
                            PHP_EOL;
                    }
                } catch (Throwable) {
                    $failedCount++;
                }
            }
        }

        return Result::success([
            'inserted' => $insertedCount,
            'failed' => $failedCount,
        ]);
    }
}
