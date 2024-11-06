<?php

namespace app\models\structure;

use app\models\Attachment;
use app\models\Base;
use app\models\BuyerDeliveryOffer;
use app\models\BuyerOffer;
use app\models\Chat;
use app\models\DeliveryPointAddress;
use app\models\FulfillmentInspectionReport;
use app\models\FulfillmentMarketplaceTransaction;
use app\models\FulfillmentOffer;
use app\models\FulfillmentPackagingLabeling;
use app\models\FulfillmentStockReport;
use app\models\Order;
use app\models\OrderDistribution;
use app\models\OrderLinkAttachment;
use app\models\OrderRate;
use app\models\OrderTracking;
use app\models\Product;
use app\models\ProductInspectionReport;
use app\models\ProductStockReport;
use app\models\Subcategory;
use app\models\TypeDelivery;
use app\models\TypeDeliveryPoint;
use app\models\TypePackaging;
use app\models\User;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "order".
 *
 * @property int $id
 * @property string $created_at
 * @property string $status
 * @property int $created_by
 * @property int|null $buyer_id
 * @property int|null $manager_id
 * @property int|null $fulfillment_id
 * @property int|null $product_id
 * @property string $product_name
 * @property string $product_description
 * @property int $expected_quantity
 * @property float $expected_price_per_item
 * @property int $expected_packaging_quantity
 * @property int $subcategory_id
 * @property int $type_packaging_id
 * @property int $type_delivery_id
 * @property int $type_delivery_point_id
 * @property int $delivery_point_address_id
 * @property float $price_product
 * @property float $price_inspection
 * @property float $price_packaging
 * @property float $price_fulfilment
 * @property float $price_delivery
 * @property int $total_quantity
 * @property int $is_need_deep_inspection
 * @property int $is_deleted
 * @property string|null $link_tz
 *
 * @property User $buyer
 * @property BuyerDeliveryOffer $buyerDeliveryOffer
 * @property BuyerOffer[] $buyerOffers
 * @property Chat[] $chats
 * @property User $createdBy
 * @property DeliveryPointAddress $deliveryPointAddress
 * @property User $fulfillment
 * @property FulfillmentInspectionReport $fulfillmentInspectionReport
 * @property FulfillmentMarketplaceTransaction[] $fulfillmentMarketplaceTransactions
 * @property FulfillmentOffer $fulfillmentOffer
 * @property FulfillmentPackagingLabeling $fulfillmentPackagingLabeling
 * @property FulfillmentStockReport $fulfillmentStockReport
 * @property User $manager
 * @property OrderDistribution $orderDistribution
 * @property OrderLinkAttachment[] $orderLinkAttachments
 * @property Attachment[] $attachments
 * //getter images with size
 * @property string[] $attachmentsWithSize
 * @property OrderRate $orderRate
 * @property OrderTracking[] $orderTrackings
 * @property Product $product
 * @property ProductInspectionReport[] $productInspectionReports
 * @property ProductStockReport[] $productStockReports
 * @property Category $category
 * @property TypeDelivery $typeDelivery
 * @property TypeDeliveryPoint $typeDeliveryPoint
 * @property TypePackaging $typePackaging
 */
class OrderStructure extends Base
{

    public function beforeSave($insert)
    {
        \app\services\UserActionLogService::log('Order beforeSave', json_encode($insert));
        return true;
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                [
                    'created_at',
                    'status',
                    'created_by',

                    'product_name_ru',
                    'product_description_ru',
                    'product_name_en',
                    'product_description_en',
                    'product_name_zh',
                    'product_description_zh',

                    'expected_quantity',
                    'expected_price_per_item',
                    'subcategory_id',
                    'type_packaging_id',
                    'type_delivery_id',
                    'type_delivery_point_id',
                    'delivery_point_address_id',
                ],
                'required',
            ],
            [['created_at'], 'safe'],
            [
                [
                    'created_by',
                    'buyer_id',
                    'manager_id',
                    'fulfillment_id',
                    'product_id',
                    'expected_quantity',
                    'expected_packaging_quantity',
                    'subcategory_id',
                    'type_packaging_id',
                    'type_delivery_id',
                    'type_delivery_point_id',
                    'delivery_point_address_id',
                    'total_quantity',
                    'is_need_deep_inspection',
                    'is_deleted',
                ],
                'integer',
            ],
            [
                [
                    'expected_price_per_item',
                    'price_product',
                    'price_inspection',
                    'price_packaging',
                    'price_fulfilment',
                    'price_delivery',
                ],
                'number',
            ],
            [['link_tz'], 'string'],
            [
                ['status', 'product_name_ru', 'product_description_ru', 'product_name_en', 'product_description_en', 'product_name_zh', 'product_description_zh'],
                'string',
                'max' => 255,
            ],
            [
                ['delivery_point_address_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => DeliveryPointAddress::class,
                'targetAttribute' => ['delivery_point_address_id' => 'id'],
            ],
            [
                ['manager_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['manager_id' => 'id'],
            ],
            [
                ['product_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Product::class,
                'targetAttribute' => ['product_id' => 'id'],
            ],
            [
                ['subcategory_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => \app\models\Category::class,
                'targetAttribute' => ['subcategory_id' => 'id'],
            ],
            [
                ['type_delivery_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => TypeDelivery::class,
                'targetAttribute' => ['type_delivery_id' => 'id'],
            ],
            [
                ['type_delivery_point_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => TypeDeliveryPoint::class,
                'targetAttribute' => ['type_delivery_point_id' => 'id'],
            ],
            [
                ['type_packaging_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => TypePackaging::class,
                'targetAttribute' => ['type_packaging_id' => 'id'],
            ],
            [
                ['created_by'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['created_by' => 'id'],
            ],
            [
                ['buyer_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['buyer_id' => 'id'],
            ],

            [
                ['fulfillment_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['fulfillment_id' => 'id'],
            ],

            // custom
            [['created_at'], 'safe'],
            [['status'], 'in', 'range' => Order::STATUS_GROUP_ALL],
            [
                ['product_name_ru', 'product_description_ru'],
                'match',
                'pattern' => '/^[A-Za-zА-Яа-я0-9\s]{1,60}$/u',
                'message' =>
                'Имя товара должно содержать кириллицу, латиницу, цифры и не превышать 60 символов. Допустимы символы: A-z, А-я, 0-9 и пробел.',
            ],
        ];
    }

    public function beforeValidate()
    {
        if (
            in_array(
                $this->getOldAttribute('status'),
                [
                    Order::STATUS_CANCELLED_ORDER,
                    Order::STATUS_CANCELLED_REQUEST,
                    Order::STATUS_COMPLETED,
                ],
                true,
            )
        ) {
            $this->addError('status', 'A cancelled order cannot be changed');

            return false;
        }

        return parent::beforeValidate();
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_at' => 'Created At',
            'status' => 'Status',
            'created_by' => 'Created By',
            'buyer_id' => 'Buyer ID',
            'manager_id' => 'Manager ID',
            'fulfillment_id' => 'Fulfillment ID',
            'product_id' => 'Product ID',

            // translations
            'product_name_ru' => 'Product Name Ru',
            'product_description_ru' => 'Product Description Ru',
            'product_name_en' => 'Product Name En',
            'product_description_en' => 'Product Description En',
            'product_name_zh' => 'Product Name Zh',
            'product_description_zh' => 'Product Description Zh',
            // end translations

            'expected_quantity' => 'Expected Quantity',
            'expected_price_per_item' => 'Expected Price Per Item',
            'expected_packaging_quantity' => 'Expected Packaging Quantity',
            'subcategory_id' => 'Subcategory ID',
            'type_packaging_id' => 'Type Packaging ID',
            'type_delivery_id' => 'Type Delivery ID',
            'type_delivery_point_id' => 'Type Delivery Point ID',
            'delivery_point_address_id' => 'Delivery Point Address ID',
            'price_product' => 'Price Product',
            'price_inspection' => 'Price Inspection',
            'price_packaging' => 'Price Packaging',
            'price_fulfilment' => 'Price Fulfilment',
            'price_delivery' => 'Price Delivery',
            'total_quantity' => 'Total Quantity',
            'is_need_deep_inspection' => 'Is Need Deep Inspection',
            'is_deleted' => 'Is Deleted',
            'link_tz' => 'Link Tz',
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
     * Gets query for [[BuyerDeliveryOffer]].
     *
     * @return ActiveQuery
     */
    public function getBuyerDeliveryOffer()
    {
        return $this->hasOne(BuyerDeliveryOffer::class, ['order_id' => 'id']);
    }

    /**
     * Gets query for [[BuyerOffers]].
     *
     * @return ActiveQuery
     */
    public function getBuyerOffers()
    {
        return $this->hasMany(BuyerOffer::class, ['order_id' => 'id']);
    }

    /**
     * Gets query for [[Chats]].
     *
     * @return ActiveQuery
     */
    public function getChats()
    {
        return $this->hasMany(Chat::class, ['order_id' => 'id']);
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[DeliveryPointAddress]].
     *
     * @return ActiveQuery
     */
    public function getDeliveryPointAddress()
    {
        return $this->hasOne(DeliveryPointAddress::class, [
            'id' => 'delivery_point_address_id',
        ]);
    }

    /**
     * Gets query for [[Fulfillment]].
     *
     * @return ActiveQuery
     */
    public function getFulfillment()
    {
        return $this->hasOne(User::class, ['id' => 'fulfillment_id']);
    }

    /**
     * Gets query for [[FulfillmentInspectionReport]].
     *
     * @return ActiveQuery
     */
    public function getFulfillmentInspectionReport()
    {
        return $this->hasOne(FulfillmentInspectionReport::class, [
            'order_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[FulfillmentMarketplaceTransactions]].
     *
     * @return ActiveQuery
     */
    public function getFulfillmentMarketplaceTransactions()
    {
        return $this->hasMany(FulfillmentMarketplaceTransaction::class, [
            'order_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[FulfillmentOffer]].
     *
     * @return ActiveQuery
     */
    public function getFulfillmentOffer()
    {
        return $this->hasOne(FulfillmentOffer::class, ['order_id' => 'id']);
    }

    /**
     * Gets query for [[FulfillmentPackagingLabeling]].
     *
     * @return ActiveQuery
     */
    public function getFulfillmentPackagingLabeling()
    {
        return $this->hasOne(FulfillmentPackagingLabeling::class, [
            'order_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[FulfillmentStockReport]].
     *
     * @return ActiveQuery
     */
    public function getFulfillmentStockReport()
    {
        return $this->hasOne(FulfillmentStockReport::class, [
            'order_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[Manager]].
     *
     * @return ActiveQuery
     */
    public function getManager()
    {
        return $this->hasOne(User::class, ['id' => 'manager_id']);
    }

    /**
     * Gets query for [[OrderDistribution]].
     *
     * @return ActiveQuery
     */
    public function getOrderDistribution()
    {
        return $this->hasOne(OrderDistribution::class, ['order_id' => 'id']);
    }

    /**
     * Gets query for [[OrderLinkAttachments]].
     *
     * @return ActiveQuery
     */
    public function getOrderLinkAttachments()
    {
        return $this->hasMany(OrderLinkAttachment::class, ['order_id' => 'id']);
    }

    /**
     * Gets query for [[Attachments]].
     *
     * @return ActiveQuery
     */
    public function getAttachments()
    {
        return $this->hasMany(Attachment::class, [
            'id' => 'attachment_id',
        ])->via('orderLinkAttachments');
    }
    public function getAttachmentsSmallSize()
    {
        return $this->hasMany(Attachment::class, ['id' => 'attachment_id'])->andOnCondition(['img_size' => 'small'])->via('orderLinkAttachments');
    }
    public function getAttachmentsMediumSize()
    {
        return $this->hasMany(Attachment::class, ['id' => 'attachment_id'])->andOnCondition(['img_size' => 'medium'])->via('orderLinkAttachments');
    }
    public function getAttachmentsLargeSize()
    {
        return $this->hasMany(Attachment::class, ['id' => 'attachment_id'])->andOnCondition(['img_size' => 'large'])->via('orderLinkAttachments');
    }

    /**
     * Gets query for [[OrderRate]].
     *
     * @return ActiveQuery
     */
    public function getOrderRate()
    {
        return $this->hasOne(OrderRate::class, ['order_id' => 'id']);
    }

    /**
     * Gets query for [[OrderTrackings]].
     *
     * @return ActiveQuery
     */
    public function getOrderTrackings()
    {
        return $this->hasMany(OrderTracking::class, ['order_id' => 'id']);
    }

    /**
     * Gets query for [[Product]].
     *
     * @return ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    /**
     * Gets query for [[ProductInspectionReports]].
     *
     * @return ActiveQuery
     */
    public function getProductInspectionReports()
    {
        return $this->hasMany(ProductInspectionReport::class, [
            'order_id' => 'id',
        ]);
    }

    /**
     * Gets query for [[ProductStockReports]].
     *
     * @return ActiveQuery
     */
    public function getProductStockReports()
    {
        return $this->hasMany(ProductStockReport::class, ['order_id' => 'id']);
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

    /**
     * Gets query for [[TypeDelivery]].
     *
     * @return ActiveQuery
     */
    public function getTypeDelivery()
    {
        return $this->hasOne(TypeDelivery::class, ['id' => 'type_delivery_id']);
    }

    /**
     * Gets query for [[TypeDeliveryPoint]].
     *
     * @return ActiveQuery
     */
    public function getTypeDeliveryPoint()
    {
        return $this->hasOne(TypeDeliveryPoint::class, [
            'id' => 'type_delivery_point_id',
        ]);
    }

    /**
     * Gets query for [[TypePackaging]].
     *
     * @return ActiveQuery
     */
    public function getTypePackaging()
    {
        return $this->hasOne(TypePackaging::class, [
            'id' => 'type_packaging_id',
        ]);
    }
}
