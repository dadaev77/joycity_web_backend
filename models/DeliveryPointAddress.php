<?php

namespace app\models;

/**
 * This is the model class for table "delivery_point_address".
 *
 * @property int $id
 * @property int $type_delivery_point_id
 * @property string $address
 * @property int|null $user_id
 * @property int $is_deleted
 *
 * @property Order[] $orders
 * @property TypeDeliveryPoint $typeDeliveryPoint
 * @property User $user
 */
class DeliveryPointAddress extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'delivery_point_address';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type_delivery_point_id', 'address'], 'required'],
            [['type_delivery_point_id', 'is_deleted', 'user_id'], 'integer'],
            [['address'], 'string', 'max' => 255],
            [['user_id'], 'unique'],
            [
                ['type_delivery_point_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => TypeDeliveryPoint::class,
                'targetAttribute' => ['type_delivery_point_id' => 'id'],
            ],
            [
                ['user_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['user_id' => 'id'],
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
            'type_delivery_point_id' => 'Type Delivery Point ID',
            'address' => 'Address',
            'user_id' => 'User ID',
            'is_deleted' => 'Is Deleted',
        ];
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::class, [
            'delivery_point_address_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[TypeDeliveryPoint]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTypeDeliveryPoint()
    {
        return $this->hasOne(TypeDeliveryPoint::class, [
            'id' => 'type_delivery_point_id',
        ]);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
