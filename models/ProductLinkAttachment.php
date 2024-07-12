<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "product_link_attachment".
 *
 * @property int $id
 * @property int $attachment_id
 * @property int $product_id
 * @property int $type
 *
 * @property Attachment $attachment
 * @property Product $product
 */
class ProductLinkAttachment extends Base
{
    public const TYPE_DEFAULT = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_link_attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['attachment_id', 'product_id', 'type'], 'required'],
            [['attachment_id', 'product_id', 'type'], 'integer'],
            [
                ['attachment_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Attachment::class,
                'targetAttribute' => ['attachment_id' => 'id'],
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
            'attachment_id' => 'Attachment ID',
            'product_id' => 'Product ID',
            'type' => 'Type',
        ];
    }

    /**
     * Gets query for [[Attachment]].
     *
     * @return ActiveQuery
     */
    public function getAttachment()
    {
        return $this->hasOne(Attachment::class, ['id' => 'attachment_id']);
    }

    /**
     * Gets query for [[Product]].
     *
     * @return ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }
}
