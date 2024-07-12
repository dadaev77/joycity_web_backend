<?php

namespace app\models;

/**
 * This is the model class for table "feedback_product_link_attachment".
 *
 * @property int $id
 * @property int $feedback_product_id
 * @property int $attachment_id
 * @property int $type
 *
 * @property Attachment $attachment
 * @property FeedbackProduct $feedbackProduct
 */
class FeedbackProductLinkAttachment extends \app\models\Base
{
    public const TYPE_DEFAULT = 0;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feedback_product_link_attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['feedback_product_id', 'attachment_id', 'type'], 'required'],
            [['feedback_product_id', 'attachment_id', 'type'], 'integer'],
            [
                ['attachment_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Attachment::class,
                'targetAttribute' => ['attachment_id' => 'id'],
            ],
            [
                ['feedback_product_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => FeedbackProduct::class,
                'targetAttribute' => ['feedback_product_id' => 'id'],
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
            'feedback_product_id' => 'Feedback Product ID',
            'attachment_id' => 'Attachment ID',
            'type' => 'Type',
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
     * Gets query for [[FeedbackProduct]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbackProduct()
    {
        return $this->hasOne(FeedbackProduct::class, [
            'id' => 'feedback_product_id',
        ]);
    }
}
