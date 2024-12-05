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
            [['order_id', 'file_path'], 'required'],
            [['order_id'], 'integer'],
            [['created_at'], 'safe'],
            [['file_path'], 'string', 'max' => 255],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::class, 'targetAttribute' => ['order_id' => 'id']],
            [['editable'], 'boolean'],
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
     * Получить URL для доступа �� файлу накладной
     */
    public function getFileUrl()
    {
        return Yii::$app->urlManager->createAbsoluteUrl(['/uploads/waybills/' . $this->file_path]);
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
