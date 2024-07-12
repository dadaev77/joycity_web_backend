<?php

namespace app\models;

/**
 * This is the model class for table "feedback_product".
 *
 * @property int $id
 * @property string $created_at
 * @property int $created_by
 * @property int $product_id
 * @property string $text
 * @property int $rating
 *
 * @property User $createdBy
 * @property FeedbackProductLinkAttachment[] $feedbackProductLinkAttachments
 * @property Product $product
 */
class FeedbackProduct extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feedback_product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                ['created_at', 'created_by', 'product_id', 'text', 'rating'],
                'required',
            ],
            [['created_at'], 'safe'],
            [['created_by', 'product_id', 'rating'], 'integer'],
            [['text'], 'string', 'max' => 750],
            [
                ['created_by'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['created_by' => 'id'],
            ],
            [
                ['product_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Product::class,
                'targetAttribute' => ['product_id' => 'id'],
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
            'product_id' => 'Product ID',
            'text' => 'Text',
            'rating' => 'Rating',
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
     * Gets query for [[FeedbackProductLinkAttachments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbackProductLinkAttachments()
    {
        return $this->hasMany(FeedbackProductLinkAttachment::class, [
            'feedback_product_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[Product]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    public function getAttachments()
    {
        return $this->hasMany(Attachment::class, [
            'id' => 'attachment_id',
        ])->via('feedbackProductLinkAttachments');
    }
}
