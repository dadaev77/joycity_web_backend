<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Модель накладной
 *
 * @property int $id
 * @property int $order_id ID заказа
 * @property string $file_path Путь к файлу накладной
 * @property string $created_at Дата создания
 * @property string|null $regenerated_at Дата регенерации
 * @property bool $editable Доступно для редактирования
 *
 * @property Order $order Связь с заказом
 */
class Waybill extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%waybill}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'regenerated_at',
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // Обязательные поля
            [['order_id', 'file_path'], 'required'],

            // Целочисленные значения
            [['order_id', 'total_number_pairs'], 'integer'],

            // Денежные значения (decimal)
            [
                ['price_per_kg', 'course', 'total_customs_duty', 'volume_costs'],
                'number',
                'numberPattern' => '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/',
                'min' => 0,
                'max' => 9999999.99
            ],

            // Дата
            ['date_of_production', 'string'],

            // Строковые значения
            [['file_path'], 'string', 'max' => 255],

            // Булево значение
            [['editable'], 'boolean'],

            // Безопасные атрибуты
            [['created_at', 'regenerated_at'], 'safe'],

            // Внешний ключ
            [
                ['order_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Order::class,
                'targetAttribute' => ['order_id' => 'id']
            ],

            // Значения по умолчанию
            [['editable'], 'default', 'value' => true],
            [['price_per_kg', 'course', 'total_customs_duty', 'volume_costs'], 'default', 'value' => 0.00],
            [['total_number_pairs'], 'default', 'value' => 0],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'ID заказа',
            'file_path' => 'Путь к файлу',
            'created_at' => 'Дата создания',
            'regenerated_at' => 'Дата регенерации',
            'editable' => 'Доступно для редактирования',
            'price_per_kg' => 'Цена за кг',
            'course' => 'Курс',
            'total_number_pairs' => 'Общее количество пар',
            'total_customs_duty' => 'Общая сумма таможенного сбора',
            'volume_costs' => 'Стоимость объема',
            'date_of_production' => 'Дата производства',
        ];
    }

    /**
     * Получить связанный заказ
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    /**
     * Получить URL для доступа  файлу накладной
     */
    public function getFileUrl()
    {
        return $_ENV['APP_URL'] . '/uploads/waybills/' . $this->file_path;
    }

    /**
     * Обновить дату регенерации
     */
    public function updateRegeneratedAt()
    {
        $this->regenerated_at = date('Y-m-d H:i:s');
        return $this->save(false);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isAttributeChanged('file_path')) {
                $this->regenerated_at = date('Y-m-d H:i:s');
            }
            return true;
        }
        return false;
    }
}
