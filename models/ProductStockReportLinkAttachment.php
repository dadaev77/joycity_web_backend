<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "product_stock_report_link_attachment".
 *
 * @property int $id
 * @property int $product_stock_report
 * @property int $attachment_id
 *
 * @property Attachment $attachment
 * @property ProductStockReport $productStockReport
 */
class ProductStockReportLinkAttachment extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_stock_report_link_attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_stock_report', 'attachment_id'], 'required'],
            [['product_stock_report', 'attachment_id'], 'integer'],
            [['attachment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Attachment::class, 'targetAttribute' => ['attachment_id' => 'id']],
            [['product_stock_report'], 'exist', 'skipOnError' => true, 'targetClass' => ProductStockReport::class, 'targetAttribute' => ['product_stock_report' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_stock_report' => 'Product Stock Report',
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
     * Gets query for [[ProductStockReport]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductStockReport()
    {
        return $this->hasOne(ProductStockReport::class, ['id' => 'product_stock_report']);
    }
}
