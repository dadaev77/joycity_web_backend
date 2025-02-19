<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "category".
 *
 * @property int $id
 * @property string $en_name
 * @property string $ru_name
 * @property string $zh_name
 * @property int $is_deleted
 * @property int $avatar_id
 *
 * @property Product[] $products
 * @property Attachment $avatar
 * @property Subcategory[] $subcategories
 * @property TypeDelivery[] $typeDeliveries
 * @property TypeDeliveryLinkCategory[] $typeDeliveryLinkCategories
 * @property UserLinkCategory[] $userLinkCategories
 */
class Category extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['en_name', 'ru_name', 'zh_name', 'avatar_id'], 'required'],
            [['is_deleted', 'avatar_id'], 'integer'],
            [['en_name', 'ru_name', 'zh_name'], 'string', 'max' => 255],
            [
                ['avatar_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Attachment::class,
                'targetAttribute' => ['avatar_id' => 'id'],
            ],
            [['parent_id'], 'integer'],
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
            'is_deleted' => 'Is Deleted',
            'avatar_id' => 'Avatar ID',
            'parent_id' => 'Parent ID',
        ];
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
     * Gets query for [[Avatar]].
     *
     * @return ActiveQuery
     */
    public function getAvatar()
    {
        return $this->hasOne(Attachment::class, ['id' => 'avatar_id']);
    }

    /**
     * Gets query for [[Subcategories]].
     *
     * @return ActiveQuery
     */
    public function getSubcategories()
    {
        return $this->hasMany(Category::class, ['parent_id' => 'id']);
    }
    
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'parent_id']);
    }

    /**
     * Gets query for [[TypeDeliveryLinkCategories]].
     *
     * @return ActiveQuery
     */
    public function getTypeDeliveryLinkCategories()
    {
        return $this->hasMany(TypeDeliveryLinkCategory::class, [
            'category_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[UserLinkCategories]].
     *
     * @return ActiveQuery
     */
    public function getUserLinkCategories()
    {
        return $this->hasMany(UserLinkCategory::class, ['category_id' => 'id']);
    }

    public function getUsers()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])->via(
            'userLinkCategories',
        );
    }

    public function getTypeDeliveries()
    {
        return $this->hasMany(TypeDelivery::class, [
            'id' => 'type_delivery_id',
        ])->via('typeDeliveryLinkCategories');
    }
}
