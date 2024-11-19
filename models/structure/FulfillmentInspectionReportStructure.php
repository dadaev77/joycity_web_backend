<?php

namespace app\models\structure;

use app\models\Base;
use app\models\FulfillmentInspectionReport;
use app\models\Order;
use app\models\User;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "fulfillment_inspection_report".
 *
 * @property int $id
 * @property string $created_at
 * @property int $order_id
 * @property int $defects_count
 * @property string $package_state
 * @property int $is_deep
 * @property int $fulfillment_id
 *
 * @property User $fulfillment
 * @property Order $order
 */
class FulfillmentInspectionReportStructure extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fulfillment_inspection_report';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                ['created_at', 'order_id', 'package_state', 'fulfillment_id'],
                'required',
            ],
            [['created_at'], 'safe'],
            [
                ['order_id', 'defects_count', 'is_deep', 'fulfillment_id'],
                'integer',
            ],
            [['package_state'], 'string', 'max' => 255],
            [
                ['package_state'],
                'in',
                'range' => [
                    FulfillmentInspectionReport::PACKAGING_CONDITION_BAD,
                    FulfillmentInspectionReport::PACKAGING_CONDITION_GOOD,
                    FulfillmentInspectionReport::PACKAGING_CONDITION_NORMAL,
                ],
            ],
            [['order_id'], 'unique'],
            [
                ['order_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Order::class,
                'targetAttribute' => ['order_id' => 'id'],
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
            'defects_count' => 'Defects Count',
            'package_state' => 'Package State',
            'is_deep' => 'Is Deep',
            'fulfillment_id' => 'Fulfillment ID',
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
