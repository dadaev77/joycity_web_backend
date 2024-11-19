<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "type_packaging".
 *
 * @property int $id
 * @property string $en_name
 * @property string $ru_name
 * @property string $zh_name
 * @property float $price
 *
 * @property Order[] $orders
 * @property UserLinkTypePackaging[] $userLinkTypePackagings
 */
class TypePackaging extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_packaging';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['en_name', 'ru_name', 'zh_name'], 'required'],
            [['price'], 'number'],
            [['en_name', 'ru_name', 'zh_name'], 'string', 'max' => 255],
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
            'price' => 'Price',
        ];
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::class, ['type_packaging_id' => 'id']);
    }

    /**
     * Gets query for [[UserLinkTypePackagings]].
     *
     * @return ActiveQuery
     */
    public function getUserLinkTypePackagings()
    {
        return $this->hasMany(UserLinkTypePackaging::class, [
            'type_packaging_id' => 'id',
        ]);
    }
}
