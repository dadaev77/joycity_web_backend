<?php

namespace app\models;

/**
 * This is the model class for table "feedback_user".
 *
 * @property int $id
 * @property string $created_at
 * @property int $created_by
 * @property string $text
 * @property string $reason
 *
 * @property User $createdBy
 * @property FeedbackUserLinkAttachment[] $feedbackUserLinkAttachments
 */
class FeedbackUser extends \app\models\Base
{
    public const REASON_BUG = 'bug';
    public const REASON_PROPOSAL = 'proposal';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feedback_user';
    }

    public static function getStatusMap()
    {
        return [
            ['key' => self::REASON_BUG, 'translate' => 'Баг'],
            ['key' => self::REASON_PROPOSAL, 'translate' => 'Предложение'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                ['reason'],
                'in',
                'range' => [
                    FeedbackUser::REASON_BUG,
                    FeedbackUser::REASON_PROPOSAL,
                ],
            ],
            [['created_at', 'created_by', 'text'], 'required'],
            [['created_at'], 'safe'],
            [['created_by'], 'integer'],
            [['reason'], 'string', 'max' => 255],

            [['text'], 'string', 'max' => 750],
            [
                ['created_by'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['created_by' => 'id'],
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
            'created_by' => 'Created By',
            'text' => 'Text',
            'reason' => 'Reason',
        ];
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[FeedbackUserLinkAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbackUserLinkAttachments()
    {
        return $this->hasMany(FeedbackUserLinkAttachment::class, [
            'feedback_user_id' => 'id',
        ]);
    }

    public function getAttachments()
    {
        return $this->hasMany(Attachment::class, [
            'id' => 'attachment_id',
        ])->via('feedbackUserLinkAttachments');
    }
}
