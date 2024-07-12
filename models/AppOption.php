<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "app_option".
 *
 * @property int $id
 * @property string|null $updated_at
 * @property string|null $key
 * @property string|null $value
 */
class AppOption extends \app\models\Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'app_option';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['updated_at'], 'safe'],
            [['value'], 'string'],
            [['key'], 'string', 'max' => 255],
            [['key'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'updated_at' => 'Updated At',
            'key' => 'Key',
            'value' => 'Value',
        ];
    }
}
