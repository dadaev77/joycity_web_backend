<?php

namespace app\models;

/**
 * This is the model class for table "fulfillment_packaging_labeling".
 *
 * @property int $id
 * @property string $created_at
 * @property int $order_id
 * @property int $fulfillment_id
 * @property int $shipped_product
 *
 * @property User $fulfillment
 * @property Order $order
 * @property PackagingReportLinkAttachment[] $packagingReportLinkAttachments
 */
class FulfillmentPackagingLabeling extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fulfillment_packaging_labeling';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'order_id', 'fulfillment_id'], 'required'],
            [['created_at'], 'safe'],
            [['order_id', 'fulfillment_id', 'shipped_product'], 'integer'],
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
            'shipped_product' => 'Shipped Product',
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

    /**
     * Gets query for [[PackagingReportLinkAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPackagingReportLinkAttachments()
    {
        return $this->hasMany(PackagingReportLinkAttachment::class, [
            'packaging_report_id' => 'id',
        ]);
    }

    public function getAttachments()
    {
        return $this->hasMany(Attachment::class, [
            'id' => 'attachment_id',
        ])->via('packagingReportLinkAttachments');
    }
}
