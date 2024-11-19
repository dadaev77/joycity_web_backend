<?php

namespace app\models\structure;

use app\models\Base;
use app\models\Order;
use app\models\OrderRate;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "order_rate".
 *
 * @property int $id
 * @property int $order_id
 * @property float $RUB
 * @property float $CNY
 * @property float $USD
 * @property string $type
 *
 * @property Order $order
 */
class OrderRateStructure extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_rate';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'RUB', 'CNY', 'USD', 'type'], 'required'],
            [['order_id'], 'integer'],
            [['RUB', 'CNY', 'USD'], 'number'],
            [['type'], 'string', 'max' => 255],
            [
                ['order_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Order::class,
                'targetAttribute' => ['order_id' => 'id'],
            ],

            // custom
            [
                ['type'],
                'in',
                'range' => [
                    OrderRate::TYPE_PRODUCT_PAYMENT,
                    OrderRate::TYPE_FULFILLMENT_PAYMENT,
                    OrderRate::TYPE_PRODUCT_DELIVERY_PAYMENT,
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
            'order_id' => 'Order ID',
            'RUB' => 'Rub',
            'CNY' => 'Cny',
            'USD' => 'Usd',
            'type' => 'Type',
        ];
    }

    /**
     * Gets query for [[Order]].
     *
     * @return ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }
}
