<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "type_delivery_link_category".
 *
 * @property int $id
 * @property int $type_delivery_id
 * @property int $category_id
 *
 * @property Category $category
 * @property TypeDelivery $typeDelivery
 */
class TypeDeliveryLinkCategory extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_delivery_link_category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type_delivery_id', 'category_id'], 'required'],
            [['type_delivery_id', 'category_id'], 'integer'],
            [
                ['category_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Category::class,
                'targetAttribute' => ['category_id' => 'id'],
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
            'category_id' => 'Category ID',
        ];
    }

    /**
     * Gets query for [[Category]].
     *
     * @return ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
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
