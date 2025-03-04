<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "push_notification".
 *
 * @property int $id
 * @property int $client_id
 * @property string $device_id
 * @property string $push_token
 */
class PushNotification extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%push_notification}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['client_id', 'device_id', 'push_token'], 'required'],
            [['client_id'], 'integer'],
            [['device_id', 'push_token'], 'string', 'max' => 255],
            [['device_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор',
            'client_id' => 'Идентификатор клиента',
            'device_id' => 'Идентификатор устройства',
            'push_token' => 'Push токен',
        ];
    }
} 