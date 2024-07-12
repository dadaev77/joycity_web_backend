<?php

namespace app\models;

/**
 * This is the model class for table "feedback_buyer_link_attachment".
 *
 * @property int $id
 * @property int $feedback_buyer_id
 * @property int $attachment_id
 * @property int $type
 *
 * @property Attachment $attachment
 * @property FeedbackBuyer $feedbackBuyer
 */
class FeedbackBuyerLinkAttachment extends \app\models\Base
{
    public const TYPE_DEFAULT = 0;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feedback_buyer_link_attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['feedback_buyer_id', 'attachment_id', 'type'], 'required'],
            [['feedback_buyer_id', 'attachment_id', 'type'], 'integer'],
            [
                ['attachment_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Attachment::class,
                'targetAttribute' => ['attachment_id' => 'id'],
            ],
            [
                ['feedback_buyer_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => FeedbackBuyer::class,
                'targetAttribute' => ['feedback_buyer_id' => 'id'],
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
            'feedback_buyer_id' => 'Feedback Buyer ID',
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
     * Gets query for [[FeedbackBuyer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbackBuyer()
    {
        return $this->hasOne(FeedbackBuyer::class, [
            'id' => 'feedback_buyer_id',
        ]);
    }
}
