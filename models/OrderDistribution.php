<?php

namespace app\models;

use yii\db\ActiveQuery;
use app\services\UserActionLogService as Log;

/**
 * This is the model class for table "order_distribution".
 *
 * @property int $id
 * @property int $order_id
 * @property int $current_buyer_id
 * @property string $requested_at
 * @property string $status
 * @property string $buyer_ids_list
 *
 * @property User $currentBuyer
 * @property Order $order
 */
class OrderDistribution extends Base
{
    public const STATUS_IN_WORK = 'in_work';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_CLOSED = 'closed';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_distribution';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                [
                    'order_id',
                    'current_buyer_id',
                    'requested_at',
                    'status',
                    'buyer_ids_list',
                ],
                'required',
            ],
            [['order_id', 'current_buyer_id'], 'integer'],
            [['requested_at'], 'safe'],
            [['buyer_ids_list'], 'string'],
            [['status'], 'string', 'max' => 255],
            [['order_id'], 'unique'],
            [
                ['current_buyer_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['current_buyer_id' => 'id'],
            ],
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
            'order_id' => 'Order ID',
            'current_buyer_id' => 'Current Buyer ID',
            'requested_at' => 'Requested At',
            'status' => 'Status',
            'buyer_ids_list' => 'Buyer Ids List',
        ];
    }

    /**
     * Gets query for [[CurrentBuyer]].
     *
     * @return ActiveQuery
     */
    public function getCurrentBuyer()
    {
        return $this->hasOne(User::class, ['id' => 'current_buyer_id']);
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
