<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

class PushDevice extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%push_services}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['user_id', 'client_id', 'device_id'], 'required'],
            [['user_id'], 'integer'],
            [['client_id', 'device_id', 'push_token'], 'string', 'max' => 255],
            [['platform'], 'string', 'max' => 20],
            [['platform'], 'in', 'range' => ['ios', 'android']],
            [['last_active_at'], 'safe'],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
} 