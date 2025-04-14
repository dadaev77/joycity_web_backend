<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель для таблицы colour
 * @property int $colour_id
 * @property string $name
 */
class Colour extends ActiveRecord
{
    const COLOURS = [
        'Черный',
        'Серый',
        'Белый',
        'Красный',
        'Оранжевый',
        'Желтый',
        'Зеленый',
        'Синий',
        'Фиолетовый',
        'Розовый'
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'colour';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required', 'message' => 'Обязательное поле'],
            [['name'], 'string', 'max' => 11],
            [['name'], 'in', 'range' => self::COLOURS, 'message' => 'Недопустимое значение цвета'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'colour_id' => 'ID цвета',
            'name' => 'Название цвета',
        ];
    }
} 