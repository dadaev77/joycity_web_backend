<?php

namespace app\models;

/**
 * This is the model class for table "attachment".
 *
 * @property int $id
 * @property string $path
 * @property int $size
 * @property string $extension
 * @property string $mime_type
 *
 * @property Category[] $categories
 * @property FeedbackBuyerLinkAttachment[] $feedbackBuyerLinkAttachments
 * @property FeedbackProductLinkAttachment[] $feedbackProductLinkAttachments
 * @property FeedbackUserLinkAttachment[] $feedbackUserLinkAttachments
 * @property FulfillmentStockReportLinkAttachment[] $fulfillmentStockReportLinkAttachments
 * @property OrderLinkAttachment[] $orderLinkAttachments
 * @property PackagingReportLinkAttachment[] $packagingReportLinkAttachments
 * @property ProductLinkAttachment[] $productLinkAttachments
 * @property ProductStockReportLinkAttachment[] $productStockReportLinkAttachments
 * @property User[] $users
 */
class Attachment extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['path', 'size', 'extension', 'mime_type'], 'required'],
            [['path'], 'string'],
            [['size'], 'integer'],
            [['extension', 'mime_type'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'path' => 'Path',
            'size' => 'Size',
            'extension' => 'Extension',
            'mime_type' => 'Mime Type',
        ];
    }

    /**
     * Gets query for [[Categories]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(Category::class, ['avatar_id' => 'id']);
    }

    /**
     * Gets query for [[FeedbackBuyerLinkAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbackBuyerLinkAttachments()
    {
        return $this->hasMany(FeedbackBuyerLinkAttachment::class, [
            'attachment_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[FeedbackProductLinkAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbackProductLinkAttachments()
    {
        return $this->hasMany(FeedbackProductLinkAttachment::class, [
            'attachment_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[FeedbackUserLinkAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbackUserLinkAttachments()
    {
        return $this->hasMany(FeedbackUserLinkAttachment::class, [
            'attachment_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[FulfillmentStockReportLinkAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFulfillmentStockReportLinkAttachments()
    {
        return $this->hasMany(FulfillmentStockReportLinkAttachment::class, [
            'attachment_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[OrderLinkAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderLinkAttachments()
    {
        return $this->hasMany(OrderLinkAttachment::class, [
            'attachment_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[PackagingReportLinkAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPackagingReportLinkAttachments()
    {
        return $this->hasMany(PackagingReportLinkAttachment::class, [
            'attachment_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[ProductLinkAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductLinkAttachments()
    {
        return $this->hasMany(ProductLinkAttachment::class, [
            'attachment_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[ProductStockReportLinkAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductStockReportLinkAttachments()
    {
        return $this->hasMany(ProductStockReportLinkAttachment::class, [
            'attachment_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[Users]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::class, ['avatar_id' => 'id']);
    }
}
