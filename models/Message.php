<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * Модель для таблицы "messages"
 *
 * @property bigint $id
 * @property bigint|null $chat_id
 * @property bigint|null $user_id
 * @property string|null $type
 * @property string|null $content
 * @property string|null $metadata
 * @property bigint|null $reply_to_id
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $edited_at
 * @property string|null $deleted_at
 * @property string|null $status
 * @property string|null $attachments
 */
class Message extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'messages';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['chat_id', 'user_id', 'reply_to_id'], 'integer'],
            [['content', 'metadata', 'attachments'], 'json'],
            [['type', 'status'], 'string'],
            [['created_at', 'updated_at', 'edited_at', 'deleted_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chat_id' => 'ID чата',
            'user_id' => 'ID пользователя',
            'type' => 'Тип сообщения',
            'content' => 'Контент сообщения',
            'metadata' => 'Метаданные',
            'reply_to_id' => 'ID родительского сообщения',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
            'edited_at' => 'Дата редактирования',
            'deleted_at' => 'Дата удаления',
            'status' => 'Статус сообщения',
            'attachments' => 'Вложения',
        ];
    }

    /**
     * Получить чат, к которому относится сообщение
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChat()
    {
        return $this->hasOne(Chat::class, ['id' => 'chat_id']);
    }

    /**
     * Получить пользователя, который отправил сообщение
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Получить сообщение, на которое отвечают
     *
     * @return \yii\db\ActiveQuery
     */
    public function getReplyToMessage()
    {
        return $this->hasOne(Message::class, ['id' => 'reply_to_id']);
    }

    /**
     * Получить статусы сообщения
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatuses()
    {
        return $this->hasMany(MessageStatus::class, ['message_id' => 'id']);
    }

    /**
     * Получить вложения сообщения
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAttachments()
    {
        return $this->hasMany(MessageAttachment::class, ['message_id' => 'id']);
    }

    /**
     * Поведение для временных меток
     *
     * @return array
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class => [
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }
}
