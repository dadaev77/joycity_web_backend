<?php

namespace app\models;

use app\components\response\ResponseCodesModels;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "feedback_buyer".
 *
 * @property int $id
 * @property string $created_at
 * @property int $created_by
 * @property int $buyer_id
 * @property string $text
 * @property int $rating
 *
 * @property User $buyer
 * @property User $createdBy
 * @property FeedbackBuyerLinkAttachment[] $feedbackBuyerLinkAttachments
 */
class FeedbackBuyer extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feedback_buyer';
    }

    public static function apiCodes(): ResponseCodesModels
    {
        return ResponseCodesModels::getStatic();
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                ['created_at', 'created_by', 'buyer_id', 'text', 'rating'],
                'required',
            ],
            [['created_at'], 'safe'],
            [['created_by', 'buyer_id', 'rating'], 'integer'],
            [['text'], 'string', 'max' => 750],
            [['rating'], 'number', 'min' => 1, 'max' => 5],
            [
                ['buyer_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['buyer_id' => 'id'],
            ],
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
            'buyer_id' => 'Buyer ID',
            'text' => 'Text',
            'rating' => 'Rating',
        ];
    }

    /**
     * Gets query for [[Buyer]].
     *
     * @return ActiveQuery
     */
    public function getBuyer()
    {
        return $this->hasOne(User::class, ['id' => 'buyer_id']);
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[FeedbackBuyerLinkAttachments]].
     *
     * @return ActiveQuery
     */
    public function getFeedbackBuyerLinkAttachments()
    {
        return $this->hasMany(FeedbackBuyerLinkAttachment::class, [
            'feedback_buyer_id' => 'id',
        ]);
    }

    public function getAttachments()
    {
        return $this->hasMany(Attachment::class, [
            'id' => 'attachment_id',
        ])->via('feedbackBuyerLinkAttachments');
    }
}
