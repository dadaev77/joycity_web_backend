<?php

namespace app\models\structure;

use app\models\FulfillmentOffer;
use app\models\Order;
use app\models\User;

/**
 * This is the model class for table "fulfillment_offer".
 *
 * @property int $id
 * @property string $created_at
 * @property int $order_id
 * @property int $fulfillment_id
 * @property string $status
 * @property float $overall_price
 *
 * @property User $fulfillment
 * @property Order $order
 */
class FulfillmentOfferStructure extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fulfillment_offer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status'], 'in', 'range' => FulfillmentOffer::STATUS_All],
            [
                [
                    'created_at',
                    'order_id',
                    'fulfillment_id',
                    'status',
                    'overall_price',
                ],
                'required',
            ],
            [['created_at'], 'safe'],
            [['order_id', 'fulfillment_id'], 'integer'],
            [['overall_price'], 'number'],
            [['status'], 'string', 'max' => 255],
            [['order_id'], 'unique'],
            [
                ['order_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Order::class,
                'targetAttribute' => ['order_id' => 'id'],
            ],
            [
                ['currency'],
                'string',
                'max' => 3,
            ],
            [
                ['fulfillment_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['fulfillment_id' => 'id'],
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
            'order_id' => 'Order ID',
            'fulfillment_id' => 'Fulfillment ID',
            'status' => 'Status',
            'overall_price' => 'Overall Price',
        ];
    }

    /**
     * Gets query for [[Fulfillment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFulfillment()
    {
        return $this->hasOne(User::class, ['id' => 'fulfillment_id']);
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
