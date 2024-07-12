<?php

namespace app\components\model;

use app\models\Base;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\BatchQueryResult;
use yii\db\Exception;

class ExtendedQuery extends ActiveQuery
{
    protected bool $showAll = false;
    protected bool $convertDecimal = true;
    public string $tableName = '';
    public array $attributes = [];

    public function showWithDeleted()
    {
        $this->showAll = true;

        return $this;
    }

    public function disableConvertDecimal()
    {
        $this->convertDecimal = false;

        return $this;
    }

    /**
     * @param null $db
     * @return array|null|ActiveRecord|static
     * @throws Exception|InvalidConfigException
     */
    public function one($db = null)
    {
        if (
            !$this->showAll &&
            in_array('is_deleted', $this->attributes, true)
        ) {
            $this->andWhere([$this->tableName . '.is_deleted' => 0]);
        }

        $result = parent::one($db);

        if ($this->convertDecimal && $result instanceof Base) {
            $result->convertDecimalAttributes();
        }

        return $result;
    }

    /**
     * @param $db
     * @return array|ActiveRecord[]|static[]
     * @throws Exception
     */
    public function all($db = null)
    {
        if (
            !$this->showAll &&
            in_array('is_deleted', $this->attributes, true)
        ) {
            $this->andWhere([$this->tableName . '.is_deleted' => 0]);
        }

        $result = parent::all($db);

        if ($this->convertDecimal) {
            foreach ($result as $entity) {
                if ($entity instanceof Base) {
                    $entity->convertDecimalAttributes();
                }
            }
        }

        return $result;
    }

    /**
     * @param $db
     * @return array
     */
    public function column($db = null)
    {
        if (
            !$this->showAll &&
            in_array('is_deleted', $this->attributes, true)
        ) {
            $this->andWhere([$this->tableName . '.is_deleted' => 0]);
        }

        $result = parent::column($db);

        if ($this->convertDecimal) {
            foreach ($result as $entity) {
                if ($entity instanceof Base) {
                    $entity->convertDecimalAttributes();
                }
            }
        }

        return $result;
    }

    /**
     * @param $db
     * @return BatchQueryResult|static[]
     */
    public function each($batchSize = 100, $db = null)
    {
        if (
            !$this->showAll &&
            in_array('is_deleted', $this->attributes, true)
        ) {
            $this->andWhere([$this->tableName . '.is_deleted' => 0]);
        }

        $result = parent::each($batchSize, $db);

        if ($this->convertDecimal) {
            foreach ($result as $entity) {
                if ($entity instanceof Base) {
                    $entity->convertDecimalAttributes();
                }
            }
        }

        return $result;
    }
}
