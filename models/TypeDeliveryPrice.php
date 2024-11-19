<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "type_delivery_price".
 *
 * @property int $id
 * @property int $type_delivery_id
 * @property int|null $range_min
 * @property int|null $range_max
 * @property float $price
 *
 * @property TypeDelivery $typeDelivery
 */
class TypeDeliveryPrice extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_delivery_price';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type_delivery_id'], 'required'],
            [['type_delivery_id', 'range_min', 'range_max'], 'integer'],
            [['price'], 'number'],
            [
                ['type_delivery_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => TypeDelivery::class,
                'targetAttribute' => ['type_delivery_id' => 'id'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type_delivery_id' => 'Type Delivery ID',
            'range_min' => 'Range Min',
            'range_max' => 'Range Max',
            'price' => 'Price',
        ];
    }

    /**
     * Gets query for [[TypeDelivery]].
     *
     * @return ActiveQuery
     */
    public function getTypeDelivery()
    {
        return $this->hasOne(TypeDelivery::class, ['id' => 'type_delivery_id']);
    }
}
