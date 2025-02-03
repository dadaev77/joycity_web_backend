<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

class ChatAttachment extends ActiveRecord
{
    public static function tableName()
    {
        return 'chat_attachments';
    }

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

    public function rules()
    {
        return [
            [['message_id', 'type', 'file_name', 'file_path', 'file_size', 'mime_type'], 'safe'],
            [['message_id'], 'required'],
            [['type'], 'in', 'range' => ['image', 'video', 'audio', 'file']],
            [['file_name', 'file_path', 'mime_type'], 'string', 'max' => 255],
            [['file_size'], 'integer'],
            [['created_at', 'updated_at'], 'datetime'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'message_id' => 'ID сообщения',
            'type' => 'Тип',
            'file_name' => 'Имя файла',
            'file_path' => 'Путь к файлу',
            'file_size' => 'Размер файла',
            'mime_type' => 'MIME тип',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
        ];
    }
}
