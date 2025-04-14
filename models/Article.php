<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Модель для таблицы article
 * @property int $id
 * @property int $product_id
 * @property int $colour_id
 * @property string $size
 * @property int $count
 * @property string $image_link_colour
 */
class Article extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'article';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id', 'colour_id', 'size', 'count'], 'required', 'message' => 'Обязательное поле'],
            [['product_id', 'colour_id', 'count'], 'integer'],
            [['size'], 'string', 'max' => 50, 'message' => 'До 50 символов'],
            [['size'], 'match', 'pattern' => '/^[A-Za-zА-Яа-я0-9\s\-.,]+$/', 'message' => 'A-Z, А-я, 0-9, "-", пробел, ".", ","'],
            [['count'], 'integer', 'min' => 1, 'max' => 50, 'message' => 'До 50 символов'],
            [['image_link_colour'], 'safe'],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::class, 'targetAttribute' => ['product_id' => 'id']],
            [['colour_id'], 'exist', 'skipOnError' => true, 'targetClass' => Colour::class, 'targetAttribute' => ['colour_id' => 'colour_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'ID товара',
            'colour_id' => 'ID цвета',
            'size' => 'Размер',
            'count' => 'Количество',
            'image_link_colour' => 'Ссылки на изображения',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getColour()
    {
        return $this->hasOne(Colour::class, ['colour_id' => 'colour_id']);
    }

    /**
     * Возвращает артикул в формате "{product_id}-{colour_id}-{size}"
     * @return string
     */
    public function getFormattedArticle()
    {
        return "{$this->product_id}-{$this->colour_id}-{$this->size}";
    }

    /**
     * {@inheritdoc}
     */
    public function fields()
    {
        $fields = parent::fields();
        $fields['formatted_article'] = 'formattedArticle';
        return $fields;
    }
} 