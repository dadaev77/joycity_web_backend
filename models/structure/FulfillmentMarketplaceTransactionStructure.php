<?php

namespace app\models\structure;

use app\models\Base;
use app\models\FulfillmentMarketplaceTransaction;
use app\models\Order;
use app\models\User;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "fulfillment_marketplace_transaction".
 *
 * @property int $id
 * @property string $created_at
 * @property int $fulfillment_id
 * @property int $order_id
 * @property int $product_count
 * @property float $amount
 * @property string $status
 *
 * @property User $fulfillment
 * @property Order $order
 */
class FulfillmentMarketplaceTransactionStructure extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fulfillment_marketplace_transaction';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                [
                    'created_at',
                    'fulfillment_id',
                    'order_id',
                    'product_count',
                    'amount',
                    'status',
                ],
                'required',
            ],
            [['created_at'], 'safe'],
            [['fulfillment_id', 'order_id', 'product_count'], 'integer'],
            [['amount'], 'number'],
            [['status'], 'string', 'max' => 255],
            [
                ['fulfillment_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['fulfillment_id' => 'id'],
            ],
            [
                ['order_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Order::class,
                'targetAttribute' => ['order_id' => 'id'],
            ],

            // custom
            [
                'status',
                'in',
                'range' => FulfillmentMarketplaceTransaction::STATUS_All,
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
            'fulfillment_id' => 'Fulfillment ID',
            'order_id' => 'Order ID',
            'product_count' => 'Product Count',
            'amount' => 'Amount',
            'status' => 'Status',
        ];
    }

    /**
     * Gets query for [[Fulfillment]].
     *
     * @return ActiveQuery
     */
    public function getFulfillment()
    {
        return $this->hasOne(User::class, ['id' => 'fulfillment_id']);
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
