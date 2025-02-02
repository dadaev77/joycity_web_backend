<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * Модель для таблицы "chats"
 *
 * @property bigint $id
 * @property string|null $type
 * @property string|null $name
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property bigint|null $last_message_id
 * @property string|null $status
 * @property bigint|null $order_id
 * @property bigint|null $verification_id
 * @property string|null $metadata
 * @property bigint|null $user_id
 * @property string|null $role
 * @property string|null $left_at
 * @property bool|null $is_muted
 * @property string|null $joined_at
 * @property bigint|null $last_read_message_id
 */
class Chat extends ActiveRecord
{
    public $auth_user_id;
    private static $dealTypes = ['order', 'deal'];
    private static $groupName = [
        'client_buyer_manager',
        'client_fulfillment_manager',
        'client_manager'
    ];

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chats';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'name', 'status', 'role'], 'string'],
            [['created_at', 'updated_at', 'left_at', 'joined_at'], 'safe'],
            [['last_message_id', 'order_id', 'verification_id', 'user_id', 'last_read_message_id'], 'integer'],
            [['metadata'], 'safe'],
            [['is_muted'], 'boolean'],
        ];
    }

    /**
     * Преобразование метаданных перед сохранением
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (is_array($this->metadata)) {
            $this->metadata = json_encode($this->metadata);
        }

        return true;
    }

    /**
     * Преобразование метаданных после загрузки
     */
    public function afterFind()
    {
        parent::afterFind();

        if ($this->metadata !== null) {
            $this->metadata = json_decode($this->metadata, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Тип',
            'name' => 'Название',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
            'last_message_id' => 'ID последнего сообщения',
            'status' => 'Статус',
            'order_id' => 'ID заказа',
            'verification_id' => 'ID верификации',
            'metadata' => 'Метаданные',
            'user_id' => 'ID пользователя',
            'role' => 'Роль',
            'left_at' => 'Дата выхода',
            'is_muted' => 'Отключены уведомления',
            'joined_at' => 'Дата присоединения',
            'last_read_message_id' => 'ID последнего прочитанного сообщения',
        ];
    }

    /**
     * Получить все сообщения чата
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMessages()
    {
        return $this->hasMany(Message::class, ['chat_id' => 'id']);
    }

    /**
     * Получить участников чата
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChatParticipants()
    {
        return $this->hasMany(ChatParticipant::class, ['chat_id' => 'id']);
    }

    /**
     * Получить последнее сообщение чата
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLastMessage()
    {
        return $this->hasOne(Message::class, ['id' => 'last_message_id']);
    }

    /**
     * Получить заказ, связанный с чатом
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    /**
     * Получить верификацию, связанную с чатом
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVerification()
    {
        return $this->hasOne(Verification::class, ['id' => 'verification_id']);
    }
}
