<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "type_delivery".
 *
 * @property int $id
 * @property string $en_name
 * @property string $ru_name
 * @property string $zh_name
 * @property int $available_for_all
 *
 * @property Order[] $orders
 * @property Category[] $categories
 * @property TypeDeliveryLinkCategory[] $typeDeliveryLinkCategories
 * @property Subcategory[] $subcategories
 * @property TypeDeliveryLinkSubcategory[] $typeDeliveryLinkSubcategories
 * @property TypeDeliveryPrice[] $typeDeliveryPrices
 * @property UserLinkTypeDelivery[] $userLinkTypeDeliveries
 */
class TypeDelivery extends Base
{
    public const AVAILABLE_FOR_ALL_TRUE = 1;
    public const AVAILABLE_FOR_ALL_FALSE = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_delivery';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['en_name', 'ru_name', 'zh_name'], 'required'],
            [['available_for_all'], 'integer'],
            [['en_name', 'ru_name', 'zh_name'], 'string', 'max' => 255],

            // custom
            [
                ['available_for_all'],
                'in',
                'range' => [
                    self::AVAILABLE_FOR_ALL_TRUE,
                    self::AVAILABLE_FOR_ALL_FALSE,
                ],
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
            'en_name' => 'En Name',
            'ru_name' => 'Ru Name',
            'zh_name' => 'Zh Name',
            'available_for_all' => 'Available For All',
        ];
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::class, ['type_delivery_id' => 'id']);
    }

    /**
     * Gets query for [[TypeDeliveryLinkCategories]].
     *
     * @return ActiveQuery
     */
    public function getTypeDeliveryLinkCategories()
    {
        return $this->hasMany(TypeDeliveryLinkCategory::class, [
            'type_delivery_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[TypeDeliveryLinkSubcategories]].
     *
     * @return ActiveQuery
     */
    public function getTypeDeliveryLinkSubcategories()
    {
        return $this->hasMany(TypeDeliveryLinkSubcategory::class, [
            'type_delivery_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[TypeDeliveryPrices]].
     *
     * @return ActiveQuery
     */
    public function getTypeDeliveryPrices()
    {
        return $this->hasMany(TypeDeliveryPrice::class, [
            'type_delivery_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[UserLinkTypeDeliveries]].
     *
     * @return ActiveQuery
     */
    public function getUserLinkTypeDeliveries()
    {
        return $this->hasMany(UserLinkTypeDelivery::class, [
            'type_delivery_id' => 'id',
        ]);
    }

    public function getCategories()
    {
        $this->hasMany(Category::class, ['id' => 'category_id'])->via(
            'typeDeliveryLinkCategories',
        );
    }

    public function getSubcategories()
    {
        $this->hasMany(Subcategory::class, ['id' => 'subcategory_id'])->via(
            'typeDeliveryLinkSubcategories',
        );
    }
}
