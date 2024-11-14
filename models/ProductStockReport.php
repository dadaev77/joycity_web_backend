<?php

namespace app\models;

use app\models\responseCodes\BuyerStockReportCodes;

/**
 * This is the model class for table "product_stock_report".
 *
 * @property int $id
 * @property string $created_at
 * @property int $order_id
 *
 * @property Order $order
 * @property ProductStockReportLinkAttachment[] $productStockReportLinkAttachments
 */
class ProductStockReport extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_stock_report';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'order_id'], 'required'],
            [['created_at'], 'safe'],
            [['order_id'], 'integer'],
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

    /**
     * Gets query for [[ProductStockReportLinkAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductStockReportLinkAttachments()
    {
        return $this->hasMany(ProductStockReportLinkAttachment::class, [
            'product_stock_report' => 'id',
        ]);
    }
    public function getAttachments()
    {
        return $this->hasMany(Attachment::class, ['id' => 'attachment_id'])
            ->andOnCondition(['img_size' => 'small'])
            ->via('productStockReportLinkAttachments');
    }

    public static function apiCodes(): BuyerStockReportCodes
    {
        return BuyerStockReportCodes::getStatic();
    }
}
