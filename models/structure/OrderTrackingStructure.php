<?php

namespace app\models\structure;

use app\models\Order;
use app\models\OrderTracking;

/**
 * This is the model class for table "order_tracking".
 *
 * @property int $id
 * @property string $created_at
 * @property string $type
 * @property int $order_id
 *
 * @property Order $order
 */
class OrderTrackingStructure extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_tracking';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type'], 'in', 'range' => OrderTracking::STATUS_ALL],
            [['created_at', 'type', 'order_id'], 'required'],
            [['created_at'], 'safe'],
            [['order_id'], 'integer'],
            [['type'], 'string', 'max' => 255],

            [
                ['order_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Order::class,
                'targetAttribute' => ['order_id' => 'id'],
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
            'created_at' => 'Created At',
            'type' => 'Type',
            'order_id' => 'Order ID',
        ];
    }

    /**
     * Gets query for [[Order]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }
}
