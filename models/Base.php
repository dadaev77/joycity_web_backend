<?php

namespace app\models;

use app\components\model\ExtendedQuery;
use app\components\response\ResponseCodesInterface;
use app\components\response\ResponseCodesModels;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;

class Base extends ActiveRecord implements ResponseCodesInterface
{
    public const TYPE_DEFAULT = 0;
    public static function apiCodes(): ResponseCodesModels
    {
        return ResponseCodesModels::getStatic();
    }

    /**
     * @param $condition
     * @param bool $withDeleted
     * @return null|BaseActiveRecord|static
     * @throws InvalidConfigException
     */
    public static function findOne($condition, bool $withDeleted = false)
    {
        $query = static::findByCondition($condition);

        if ($withDeleted) {
            $query->showWithDeleted();
        }

        return $query->one();
    }

    /**
     * @param $condition
     * @return BaseActiveRecord[]|static[]
     * @throws InvalidConfigException
     */
    public static function findAll($condition, bool $withDeleted = false)
    {
        $query = static::findByCondition($condition);

        if ($withDeleted) {
            $query->showWithDeleted();
        }

        return $query->all();
    }

    public static function isset(
        array|int $condition,
        bool $showDeleted = false,
    ): bool {
        $query = static::find()->select(['id']);

        if (is_array($condition)) {
            $query->where($condition);
        }

        if (is_int($condition)) {
            $query->where(['id' => $condition]);
        }
        if ($showDeleted) {
            $query->showWithDeleted();
        }

        return (bool) $query->one();
    }

    /**
     * @return ExtendedQuery|static
     */
    public static function find()
    {
        return new ExtendedQuery(static::class, [
            'tableName' => static::tableName(),
            'attributes' => array_keys((new static())->getAttributes()),
        ]);
    }

    public function linkAll(
        string $relationName,
        array $collection,
        array $extraColumns = [],
    ) {
        $this->unlinkAll($relationName, true);

        foreach ($collection as $model) {
            $this->link($relationName, $model, $extraColumns);
        }
    }

    public function convertDecimalAttributes(): void
    {
        foreach ($this->attributes as $attribute => $value) {
            if (
                $this->isDecimalType($attribute) &&
                $this->getAttribute($attribute) !== null
            ) {
                $this->setAttribute($attribute, (float) $value);
            }
        }
    }

    private function isDecimalType($attribute): bool
    {
        $column = self::getTableSchema()->getColumn($attribute);

        return $column !== null && $column->type === 'decimal';
    }
}
