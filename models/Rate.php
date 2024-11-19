<?php

namespace app\models;

/**
 * This is the model class for table "rate".
 *
 * @property int $id
 * @property float $RUB
 * @property float $CNY
 * @property float $USD
 * @property string $created_at
 */
class Rate extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rate';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['RUB', 'CNY', 'USD'], 'required'],
            [['RUB', 'CNY', 'USD'], 'number'],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'RUB' => 'Rub',
            'CNY' => 'Cny',
            'USD' => 'Usd',
            'created_at' => 'Created At',
        ];
    }
}
