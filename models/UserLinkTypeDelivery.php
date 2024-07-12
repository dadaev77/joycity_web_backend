<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "type_delivery_has_user".
 *
 * @property int $id
 * @property int $type_delivery_id
 * @property int $user_id
 *
 * @property TypeDelivery $typeDelivery
 * @property User $user
 */
class UserLinkTypeDelivery extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_link_type_delivery';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type_delivery_id', 'user_id'], 'required'],
            [['type_delivery_id', 'user_id'], 'integer'],
            [
                ['type_delivery_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => TypeDelivery::class,
                'targetAttribute' => ['type_delivery_id' => 'id'],
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
            'type_delivery_id' => 'Type Delivery ID',
            'user_id' => 'User ID',
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

    /**
     * Gets query for [[User]].
     *
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
