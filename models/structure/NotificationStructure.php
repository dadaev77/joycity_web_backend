<?php

namespace app\models\structure;

use app\models\Base;
use app\models\Notification;
use app\models\User;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "notification".
 *
 * @property int $id
 * @property string $created_at
 * @property int $user_id
 * @property int $is_read
 * @property string $event
 * @property string $entity_type
 * @property int $entity_id
 *
 * @property User $user
 */
class NotificationStructure extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                ['created_at', 'user_id', 'event', 'entity_type', 'entity_id'],
                'required',
            ],
            [['created_at'], 'safe'],
            [['user_id', 'is_read', 'entity_id'], 'integer'],
            [['event', 'entity_type'], 'string', 'max' => 255],
            [
                ['user_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['user_id' => 'id'],
            ],

            // custom
            [
                ['entity_type'],
                'in',
                'range' => [
                    Notification::ENTITY_TYPE_ORDER,
                    Notification::ENTITY_TYPE_VERIFICATION,
                ],
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
            'user_id' => 'User ID',
            'is_read' => 'Is Read',
            'event' => 'Event',
            'entity_type' => 'Entity Type',
            'entity_id' => 'Entity ID',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
