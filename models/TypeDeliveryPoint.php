<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "type_delivery_point".
 *
 * @property int $id
 * @property string $en_name
 * @property string $ru_name
 * @property string $zh_name
 *
 * @property DeliveryPointAddress[] $deliveryPointAddresses
 * @property Order[] $orders
 */
class TypeDeliveryPoint extends Base
{
    public const TYPE_WAREHOUSE = 1;
    public const TYPE_FULFILLMENT = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'type_delivery_point';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['en_name', 'ru_name', 'zh_name'], 'required'],
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
        ];
    }

    /**
     * Gets query for [[DeliveryPointAddresses]].
     *
     * @return ActiveQuery
     */
    public function getDeliveryPointAddresses()
    {
        return $this->hasMany(DeliveryPointAddress::class, [
            'type_delivery_point_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::class, ['type_delivery_point_id' => 'id']);
    }
}
