<?php

namespace app\models;

/**
 * This is the model class for table "feedback_user_link_attachment".
 *
 * @property int $id
 * @property int $feedback_user_id
 * @property int $attachment_id
 *
 * @property Attachment $attachment
 * @property FeedbackUser $feedbackUser
 */
class FeedbackUserLinkAttachment extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feedback_user_link_attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['feedback_user_id', 'attachment_id'], 'required'],
            [['feedback_user_id', 'attachment_id'], 'integer'],
            [
                ['attachment_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Attachment::class,
                'targetAttribute' => ['attachment_id' => 'id'],
            ],
            [
                ['feedback_user_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => FeedbackUser::class,
                'targetAttribute' => ['feedback_user_id' => 'id'],
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
            'feedback_user_id' => 'Feedback User ID',
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
     * Gets query for [[FeedbackUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbackUser()
    {
        return $this->hasOne(FeedbackUser::class, ['id' => 'feedback_user_id']);
    }
}
