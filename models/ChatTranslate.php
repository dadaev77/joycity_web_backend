<?php

namespace app\models;

/**
 * This is the model class for table "chat_translate".
 *
 * @property int $id
 * @property string $message_key
 * @property string $ru
 * @property string $zh
 * @property string $en
 */
class ChatTranslate extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_translate';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['message_key', 'ru', 'zh', 'en'], 'required'],
            [['ru', 'zh', 'en'], 'string'],
            [['message_key'], 'string', 'max' => 255],
            [['message_key'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'message_key' => 'Message Key',
            'ru' => 'Ru',
            'zh' => 'Zh',
            'en' => 'En',
        ];
    }
}
