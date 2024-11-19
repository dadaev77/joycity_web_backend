<?php

namespace app\models;

/**
 * This is the model class for table "packaging_report_link_attachment".
 *
 * @property int $id
 * @property int $packaging_report_id
 * @property int $attachment_id
 *
 * @property Attachment $attachment
 * @property FulfillmentPackagingLabeling $packagingReport
 */
class PackagingReportLinkAttachment extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'packaging_report_link_attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['packaging_report_id', 'attachment_id'], 'required'],
            [['packaging_report_id', 'attachment_id'], 'integer'],
            [
                ['attachment_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Attachment::class,
                'targetAttribute' => ['attachment_id' => 'id'],
            ],
            [
                ['packaging_report_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => FulfillmentPackagingLabeling::class,
                'targetAttribute' => ['packaging_report_id' => 'id'],
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
            'packaging_report_id' => 'Packaging Report ID',
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
     * Gets query for [[PackagingReport]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPackagingReport()
    {
        return $this->hasOne(FulfillmentPackagingLabeling::class, [
            'id' => 'packaging_report_id',
        ]);
    }
}
