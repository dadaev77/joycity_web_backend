<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * This is the model class for table "order_link_attachment".
 *
 * @property int $id
 * @property int $order_id
 * @property int $attachment_id
 *
 * @property Attachment $attachment
 * @property Order $order
 */
class OrderLinkAttachment extends Base
{
    public const TYPE_DEFAULT = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_link_attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'attachment_id'], 'required'],
            [['order_id', 'attachment_id'], 'integer'],
            [
                ['attachment_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Attachment::class,
                'targetAttribute' => ['attachment_id' => 'id'],
            ],
            [
                ['order_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Order::class,
                'targetAttribute' => ['order_id' => 'id'],
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
            'order_id' => 'Order ID',
            'attachment_id' => 'Attachment ID',
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
     * Gets query for [[Order]].
     *
     * @return ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }
}
