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
     * @var array Массив вложений
     */
    private $_attachments = [];

    public function getAttachments()
    {
        return $this->_attachments;
    }

    public function setAttachments($value)
    {
        $this->_attachments = $value;
    }

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
            [['content', 'metadata', 'attachments'], 'safe'],
            [['type', 'status'], 'string'],
            [['created_at', 'updated_at', 'edited_at', 'deleted_at'], 'safe'],
        ];
    }

    /**
     * Преобразование JSON полей перед сохранением
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (is_array($this->content)) {
            $this->content = json_encode($this->content);
        }

        if (is_array($this->metadata)) {
            $this->metadata = json_encode($this->metadata);
        }

        // Сохраняем вложения в JSON
        if (!empty($this->_attachments)) {
            $this->attachments = json_encode($this->_attachments);
        }

        return true;
    }

    /**
     * Преобразование JSON полей после загрузки
     */
    public function afterFind()
    {
        parent::afterFind();

        if ($this->metadata !== null) {
            $this->metadata = json_decode($this->metadata, true);
        }
        if ($this->content !== null) {
            $this->content = json_decode($this->content, true);
        }
        if ($this->attachments !== null) {
            $this->_attachments = json_decode($this->attachments, true);
        }
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
     * Поведение для временных меток
     *
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    self::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => new Expression('NOW()'),
            ],
        ];
    }
}
