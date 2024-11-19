<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "subcategory".
 *
 * @property int $id
 * @property string $en_name
 * @property string $ru_name
 * @property string $zh_name
 * @property int $category_id
 * @property int $is_deleted
 * @property Category $category
 * @property Order[] $orders
 * @property Product[] $products
 * @property TypeDeliveryLinkSubcategory[] $typeDeliveryLinkSubcategories
 * @property TypeDelivery[] $typeDeliveries
 */
class Subcategory extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subcategory';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['en_name', 'ru_name', 'zh_name', 'category_id'], 'required'],
            [['category_id', 'is_deleted'], 'integer'],
            [['en_name', 'ru_name', 'zh_name'], 'string', 'max' => 255],
            [
                ['category_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Category::class,
                'targetAttribute' => ['category_id' => 'id'],
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
            'category_id' => 'Category ID',
            'is_deleted' => 'Is Deleted',
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
     * Gets query for [[Orders]].
     *
     * @return ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::class, ['subcategory_id' => 'id']);
    }

    /**
     * Gets query for [[Products]].
     *
     * @return ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(Product::class, ['subcategory_id' => 'id']);
    }

    /**
     * Gets query for [[TypeDeliveryLinkSubcategories]].
     *
     * @return ActiveQuery
     */
    public function getTypeDeliveryLinkSubcategories()
    {
        return $this->hasMany(TypeDeliveryLinkSubcategory::class, [
            'subcategory_id' => 'id',
        ]);
    }

    public function getTypeDeliveries()
    {
        return $this->hasMany(TypeDelivery::class, [
            'id' => 'type_delivery_id',
        ])->via('typeDeliveryLinkSubcategories');
    }
}
