<?php

namespace app\helpers;

use app\models\Base;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class ModelTypeHelper
{
    public static function collection(
        string $modelClass,
        array $response
    ): array {
        return array_map(
            static fn($item): array => self::fromArray($modelClass, $item),
            $response
        );
    }

    public static function fromArray(string $modelClass, array $response): array
    {
        /** @var Base $modelClass */
        $tableSchema = $modelClass::getTableSchema();

        foreach ($response as $key => $value) {
            if ($info = $tableSchema->getColumn($key)) {
                settype($response[$key], $info->phpType);
            }
        }

        return $response;
    }

    public static function toArrayCollection(array $collection): array
    {
        return array_map(static function ($model) {
            return self::toArray($model);
        }, $collection);
    }

    public static function toArray(Base|ActiveRecord $model): array
    {
        $out = ArrayHelper::toArray($model);

        foreach ($model->getRelatedRecords() as $key => $relation) {
            if (is_null($relation)) {
                $out[$key] = null;
            } elseif (is_array($relation)) {
                $out[$key] = array_map(
                    static fn($item) => self::toArray($item),
                    $relation
                );
            } else {
                $out[$key] = self::toArray($relation);
            }
        }

        return $out;
    }
}
