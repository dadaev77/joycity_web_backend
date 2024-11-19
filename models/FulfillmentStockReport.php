<?php

namespace app\models;

use app\models\responseCodes\FulfillmentStockReportCodes;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "fulfillment_stock_report".
 *
 * @property int $id
 * @property string $created_at
 * @property int $order_id
 * @property int $fulfillment_id
 *
 * @property User $fulfillment
 * @property FulfillmentStockReportLinkAttachment[] $fulfillmentStockReportLinkAttachments
 * @property Order $order
 */
class FulfillmentStockReport extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fulfillment_stock_report';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'order_id', 'fulfillment_id'], 'required'],
            [['created_at'], 'safe'],
            [['order_id', 'fulfillment_id'], 'integer'],
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
     * Gets query for [[FulfillmentStockReportLinkAttachments]].
     *
     * @return ActiveQuery
     */
    public function getFulfillmentStockReportLinkAttachments()
    {
        return $this->hasMany(FulfillmentStockReportLinkAttachment::class, [
            'fulfillment_stock_report_id' => 'id',
        ]);
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

    public function getAttachments()
    {
        return $this->hasMany(Attachment::class, [
            'id' => 'attachment_id',
        ])->via('fulfillmentStockReportLinkAttachments');
    }

    public static function apiCodes(): FulfillmentStockReportCodes
    {
        return FulfillmentStockReportCodes::getStatic();
    }
}
