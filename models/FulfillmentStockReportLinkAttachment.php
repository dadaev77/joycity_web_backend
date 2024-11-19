<?php

namespace app\models;

/**
 * This is the model class for table "fulfillment_stock_report_link_attachment".
 *
 * @property int $id
 * @property int $fulfillment_stock_report_id
 * @property int $attachment_id
 *
 * @property Attachment $attachment
 * @property FulfillmentStockReport $fulfillmentStockReport
 */
class FulfillmentStockReportLinkAttachment extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fulfillment_stock_report_link_attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fulfillment_stock_report_id', 'attachment_id'], 'required'],
            [['fulfillment_stock_report_id', 'attachment_id'], 'integer'],
            [
                ['attachment_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Attachment::class,
                'targetAttribute' => ['attachment_id' => 'id'],
            ],
            [
                ['fulfillment_stock_report_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => FulfillmentStockReport::class,
                'targetAttribute' => ['fulfillment_stock_report_id' => 'id'],
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
            'fulfillment_stock_report_id' => 'Fulfillment Stock Report ID',
            'attachment_id' => 'Attachment ID',
        ];
    }

    /**
     * Gets query for [[Attachment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::class, ['id' => 'attachment_id']);
    }

    /**
     * Gets query for [[FulfillmentStockReport]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFulfillmentStockReport()
    {
        return $this->hasOne(FulfillmentStockReport::class, [
            'id' => 'fulfillment_stock_report_id',
        ]);
    }
}
