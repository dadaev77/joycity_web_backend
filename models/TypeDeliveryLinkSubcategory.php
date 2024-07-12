<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "type_delivery_link_subcategory".
 *
 * @property int $id
 * @property int $type_delivery_id
 * @property int $subcategory_id
 *
 * @property Subcategory $subcategory
 * @property TypeDelivery $typeDelivery
 */
class TypeDeliveryLinkSubcategory extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_delivery_link_subcategory';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type_delivery_id', 'subcategory_id'], 'required'],
            [['type_delivery_id', 'subcategory_id'], 'integer'],
            [
                ['subcategory_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Subcategory::class,
                'targetAttribute' => ['subcategory_id' => 'id'],
            ],
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
            'subcategory_id' => 'Subcategory ID',
        ];
    }

    /**
     * Gets query for [[Subcategory]].
     *
     * @return ActiveQuery
     */
    public function getSubcategory()
    {
        return $this->hasOne(Subcategory::class, ['id' => 'subcategory_id']);
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
