<?php

namespace app\models\structure;

use app\models\Base;
use app\models\Order;
use app\models\User;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "buyer_offer".
 *
 * @property int $id
 * @property string $created_at
 * @property int $order_id
 * @property int $buyer_id
 * @property int $status
 * @property float $price_product
 * @property float $price_inspection
 * @property int $total_quantity
 * @property float $product_height
 * @property float $product_width
 * @property float $product_depth
 * @property float $product_weight
 *
 * @property User $buyer
 * @property Order $order
 */
class BuyerOfferStructure extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'buyer_offer';
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
                    'order_id',
                    'buyer_id',
                    'status',
                    'price_product',
                    'total_quantity',
                ],
                'required',
            ],
            [['price_inspection'], 'skipOnEmpty' => true],
            [['created_at'], 'safe'],
            [['order_id', 'buyer_id', 'status', 'total_quantity'], 'integer'],
            [
                [
                    'price_product',
                    'price_inspection',
                    'product_height',
                    'product_width',
                    'product_depth',
                    'product_weight',
                ],
                'number',
            ],

            [
                ['order_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Order::class,
                'targetAttribute' => ['order_id' => 'id'],
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
            'created_at' => 'Created At',
            'order_id' => 'Order ID',
            'buyer_id' => 'Buyer ID',
            'status' => 'Status',
            'price_product' => 'Price Product',
            'price_inspection' => 'Price Inspection',
            'total_quantity' => 'Total Quantity',
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
     * Gets query for [[Order]].
     *
     * @return ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }
}
