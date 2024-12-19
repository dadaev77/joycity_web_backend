<?php

namespace app\models;

use app\components\response\ResponseCodesModels;
use yii\db\ActiveQuery;
use app\services\RateService;

/**
 * This is the model class for table "product".
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property float $rating
 * @property int $feedback_count
 * @property int $buyer_id
 * @property int $subcategory_id
 * @property int $range_1_min
 * @property int $range_1_max
 * @property float $range_1_price
 * @property int|null $range_2_min
 * @property int|null $range_2_max
 * @property float|null $range_2_price
 * @property int|null $range_3_min
 * @property int|null $range_3_max
 * @property float|null $range_3_price
 * @property int|null $range_4_min
 * @property int|null $range_4_max
 * @property float|null $range_4_price
 * @property int $is_deleted
 * @property float $product_height
 * @property float $product_width
 * @property float $product_depth
 * @property float $product_weight
 *
 * @property User $buyer
 * @property FeedbackProduct[] $feedbackProducts
 * @property Order[] $orders
 * @property ProductLinkAttachment[] $productLinkAttachments
 * @property Attachment[] $attachments
 * @property Subcategory $subcategory
 */
class Product extends Base
{
    public function beforeSave($insert)
    {
        return parent::beforeSave($insert);
    }

    public static function apiCodes(): ResponseCodesModels
    {
        return ResponseCodesModels::getStatic();
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                [
                    'name_ru',
                    'description_ru',
                    'buyer_id',
                    'subcategory_id',
                    'range_1_max',
                    'range_1_price',
                ],
                'required',
            ],
            [['description_ru'], 'string', 'max' => 1000],
            [
                ['description_ru'],
                'match',
                'pattern' => '/^[\x{201C}\x{201D}\x{2018}\x{2019}\x{AB}\x{BB}\'"()!:\';*%\-.,\d\s\p{Cyrillic}\p{Latin}\p{Han}\p{M}]*$/u',
                'message' => 'Описание может содержать буквы, цифры, пробелы и специальные символы'
            ],
            [
                [
                    'rating',
                    'range_1_price',
                    'range_2_price',
                    'range_3_price',
                    'range_4_price',
                    'product_height',
                    'product_width',
                    'product_depth',
                    'product_weight',
                ],
                'number',
            ],
            [
                [
                    'feedback_count',
                    'buyer_id',
                    'subcategory_id',
                    'range_1_min',
                    'range_1_max',
                    'range_2_min',
                    'range_2_max',
                    'range_3_min',
                    'range_3_max',
                    'range_4_min',
                    'range_4_max',
                    'is_deleted',
                ],
                'integer',
            ],
            [['name_ru'], 'string', 'max' => 255],
            [
                ['name_ru'],
                'match',
                'pattern' => '/^[\x{201C}\x{201D}\x{2018}\x{2019}\x{AB}\x{BB}\'"()!:\';*%\-.,\d\s\p{Cyrillic}\p{Latin}\p{Han}\p{M}]*$/u',
                'message' => 'Название может содержать буквы, цифры, пробелы и специальные символы.',
            ],
            [
                ['subcategory_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => \app\models\Category::class,
                'targetAttribute' => ['subcategory_id' => 'id'],
            ],
            [
                ['buyer_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['buyer_id' => 'id'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',

            // translations
            'name_ru' => 'Name',
            'description_ru' => 'Description',
            'name_eng' => 'Name Eng',
            'description_eng' => 'Description Eng',
            'name_zh' => 'Name Zh',
            'description_zh' => 'Description Zh',
            // end translations

            'rating' => 'Rating',
            'feedback_count' => 'Feedback Count',
            'buyer_id' => 'Buyer ID',
            'subcategory_id' => 'Subcategory ID',
            'range_1_min' => 'Range 1 Min',
            'range_1_max' => 'Range 1 Max',
            'range_1_price' => 'Range 1 Price',
            'range_2_min' => 'Range 2 Min',
            'range_2_max' => 'Range 2 Max',
            'range_2_price' => 'Range 2 Price',
            'range_3_min' => 'Range 3 Min',
            'range_3_max' => 'Range 3 Max',
            'range_3_price' => 'Range 3 Price',
            'range_4_min' => 'Range 4 Min',
            'range_4_max' => 'Range 4 Max',
            'range_4_price' => 'Range 4 Price',
            'is_deleted' => 'Is Deleted',
            'product_height' => 'Product Height',
            'product_width' => 'Product Width',
            'product_depth' => 'Product Depth',
            'product_weight' => 'Product Weight',
        ];
    }

    /**
     * Gets query for [[Buyer]].
     *
     * @return ActiveQuery
     */
    public function getBuyer()
    {
        return $this->hasOne(User::class, ['id' => 'buyer_id']);
    }

    /**
     * Gets query for [[FeedbackProducts]].
     *
     * @return ActiveQuery
     */
    public function getFeedbackProducts()
    {
        return $this->hasMany(FeedbackProduct::class, ['product_id' => 'id']);
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::class, ['product_id' => 'id']);
    }

    /**
     * Gets query for [[ProductLinkAttachments]].
     *
     * @return ActiveQuery
     */
    public function getProductLinkAttachments()
    {
        return $this->hasMany(ProductLinkAttachment::class, [
            'product_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[Attachments]].
     *
     * @return ActiveQuery
     */
    public function getAttachments()
    {
        return $this->hasMany(Attachment::class, ['id' => 'attachment_id'])->via('productLinkAttachments');
    }

    public function getAttachmentsSmallSize()
    {
        return $this->hasMany(Attachment::class, ['id' => 'attachment_id'])->andOnCondition(['img_size' => 'small'])->via('productLinkAttachments');
    }

    public function getAttachmentsMediumSize()
    {
        return $this->hasMany(Attachment::class, ['id' => 'attachment_id'])->andOnCondition(['img_size' => 'medium'])->via('productLinkAttachments');
    }

    public function getAttachmentsLargeSize()
    {
        return $this->hasMany(Attachment::class, ['id' => 'attachment_id'])->andOnCondition(['img_size' => 'large'])->via('productLinkAttachments');
    }

    /**
     * Gets query for [[Subcategory]].
     *
     * @return ActiveQuery
     */
    public function getSubcategory()
    {
        return $this->hasOne(\app\models\Category::class, ['id' => 'subcategory_id']);
    }
    /**
     * Gets query for [[Category]].
     *
     * @return ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(\app\models\Category::class, ['id' => 'parent_id']);
    }
}
