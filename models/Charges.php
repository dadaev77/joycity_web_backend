<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

class Charges extends ActiveRecord
{
    public static function tableName()
    {
        return 'charges';
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
            [['usd_charge', 'cny_charge'], 'required'],
            [['usd_charge', 'cny_charge'], 'integer'],
            [['usd_charge', 'cny_charge'], 'default', 'value' => 2],
            ['usd_charge', 'default', 'value' => 2],
            ['cny_charge', 'default', 'value' => 5],
            [['usd_charge', 'cny_charge'], 'number', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usd_charge' => 'Наценка на курс доллара',
            'cny_charge' => 'Наценка на курс юаня',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    /**
     * Получает текущие значения наценок
     * @return array|null
     */
    public static function getCurrentCharges()
    {
        $charges = self::find()->one();
        return $charges ? [
            'usd_charge' => $charges->usd_charge,
            'cny_charge' => $charges->cny_charge,
        ] : null;
    }
} 