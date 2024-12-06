<?php

namespace app\models\structure;

use app\models\Base;
use app\models\BuyerDeliveryOffer;
use app\models\Order;
use app\models\User;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "buyer_delivery_offer".
 *
 * @property int $id
 * @property string $created_at
 * @property int $order_id
 * @property int $buyer_id
 * @property int $manager_id
 * @property string $status
 * @property float $price_product
 * @property int $total_quantity
 * @property int $total_packaging_quantity
 * @property float $product_height
 * @property float $product_width
 * @property float $product_depth
 * @property float $product_weight
 * @property float $package_expenses
 * @property int $amount_of_space
 *
 * @property User $buyer
 * @property User $manager
 * @property Order $order
 */
class BuyerDeliveryOfferStructure extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'buyer_delivery_offer';
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
                    'manager_id',
                    'status',
                    'price_product',
                    'total_quantity',
                    'package_expenses',
                    'amount_of_space',
                    'currency',
                ],
                'required',
            ],
            [['created_at'], 'safe'],
            [
                ['order_id', 'buyer_id', 'manager_id', 'total_quantity', 'amount_of_space'],
                'integer',
            ],
            [
                [
                    'price_product',
                    'product_height',
                    'product_width',
                    'product_depth',
                    'product_weight',
                    'package_expenses'
                ],
                'number',
            ],
            [['status'], 'string', 'max' => 255],
            [['order_id'], 'unique'],
            [
                ['buyer_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['buyer_id' => 'id'],
            ],
            [
                ['manager_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => User::class,
                'targetAttribute' => ['manager_id' => 'id'],
            ],
            [
                ['order_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Order::class,
                'targetAttribute' => ['order_id' => 'id'],
            ],

            // custom
            [['status'], 'in', 'range' => BuyerDeliveryOffer::STATUS_GROUP_ALL],
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
            'manager_id' => 'Manager ID',
            'status' => 'Status',
            'price_product' => 'Price Product',
            'total_quantity' => 'Total Quantity',
            'product_height' => 'Product Height',
            'product_width' => 'Product Width',
            'product_depth' => 'Product Depth',
            'product_weight' => 'Product Weight',
            'package_expenses' => 'Package Expenses',
            'amount_of_space' => 'Amount Of Space',
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
     * Gets query for [[Manager]].
     *
     * @return ActiveQuery
     */
    public function getManager()
    {
        return $this->hasOne(User::class, ['id' => 'manager_id']);
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
