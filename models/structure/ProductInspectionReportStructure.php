<?php

namespace app\models\structure;

use app\models\Order;
use app\models\ProductInspectionReport;

/**
 * This is the model class for table "product_inspection_report".
 *
 * @property int $id
 * @property string $created_at
 * @property int $order_id
 * @property int $defects_count
 * @property string $package_state
 * @property int $is_deep
 *
 * @property Order $order
 */
class ProductInspectionReportStructure extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_inspection_report';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'order_id', 'package_state'], 'required'],
            [['created_at'], 'safe'],
            [['order_id', 'defects_count', 'is_deep'], 'integer'],
            [['package_state'], 'string', 'max' => 255],
            [
                ['package_state'],
                'in',
                'range' => [
                    ProductInspectionReport::PACKAGING_CONDITION_BAD,
                    ProductInspectionReport::PACKAGING_CONDITION_GOOD,
                    ProductInspectionReport::PACKAGING_CONDITION_NORMAL,
                ],
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
            'created_at' => 'Created At',
            'order_id' => 'Order ID',
            'defects_count' => 'Defects Count',
            'package_state' => 'Package State',
            'is_deep' => 'Is Deep',
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
