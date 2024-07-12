<?php

use app\models\TypeDelivery;
use app\services\price\OrderDeliveryPriceService;
use yii\db\Migration;

/**
 * Class m240213_170529_delivery_price_calculation
 */
class m240213_170529_delivery_price_calculation extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Внедрение габаритов
        $this->implementingProductSizes();

        // Удаление лишних полей buyer offer
        $this->buyerOfferImprovements();

        // Таблица с ценами типов доставки
        $this->createTypeDeliveryPriceTables();

        $this->addColumn(
            'type_packaging',
            'price',
            $this->decimal(10, 4)
                ->notNull()
                ->defaultValue(0),
        );

        $this->addColumn(
            'order',
            'expected_packaging_quantity',
            $this->integer()
                ->notNull()
                ->defaultValue(0)
                ->after('expected_price_per_item'),
        );

        // Заполнение цен для всех типов доставки
        $this->fillingTypeDeliveryPrices();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240213_170529_delivery_price_calculation cannot be reverted.\n";

        return false;
    }

    protected function implementingProductSizes()
    {
        $this->addColumn(
            'product',
            'product_height',
            $this->decimal(8, 4)
                ->notNull()
                ->defaultValue(0),
        );
        $this->addColumn(
            'product',
            'product_width',
            $this->decimal(8, 4)
                ->notNull()
                ->defaultValue(0),
        );
        $this->addColumn(
            'product',
            'product_depth',
            $this->decimal(8, 4)
                ->notNull()
                ->defaultValue(0),
        );
        $this->addColumn(
            'product',
            'product_weight',
            $this->decimal(8, 4)
                ->notNull()
                ->defaultValue(0),
        );

        $this->addColumn(
            'buyer_offer',
            'product_height',
            $this->decimal(8, 4)
                ->notNull()
                ->defaultValue(0),
        );
        $this->addColumn(
            'buyer_offer',
            'product_width',
            $this->decimal(8, 4)
                ->notNull()
                ->defaultValue(0),
        );
        $this->addColumn(
            'buyer_offer',
            'product_depth',
            $this->decimal(8, 4)
                ->notNull()
                ->defaultValue(0),
        );
        $this->addColumn(
            'buyer_offer',
            'product_weight',
            $this->decimal(8, 4)
                ->notNull()
                ->defaultValue(0),
        );

        $this->createTable('buyer_delivery_offer', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'order_id' => $this->integer()->notNull(),
            'buyer_id' => $this->integer()->notNull(),
            'manager_id' => $this->integer()->notNull(),
            'status' => $this->string()->notNull(),
            'price_product' => $this->decimal(12, 4)->notNull(),
            'total_quantity' => $this->integer()->notNull(),
            'total_packaging_quantity' => $this->integer()->notNull(),
            'product_height' => $this->decimal(8, 4)
                ->notNull()
                ->defaultValue(0),
            'product_width' => $this->decimal(8, 4)
                ->notNull()
                ->defaultValue(0),
            'product_depth' => $this->decimal(8, 4)
                ->notNull()
                ->defaultValue(0),
            'product_weight' => $this->decimal(8, 4)
                ->notNull()
                ->defaultValue(0),
        ]);

        $this->createIndex(
            'idx_buyer_delivery_offer_order_id',
            'buyer_delivery_offer',
            'order_id',
            true,
        );

        $this->addForeignKey(
            'fk_buyer_delivery_offer_order_id',
            'buyer_delivery_offer',
            'order_id',
            'order',
            'id',
        );

        $this->addForeignKey(
            'fk_buyer_delivery_offer_buyer_id',
            'buyer_delivery_offer',
            'buyer_id',
            'user',
            'id',
        );

        $this->addForeignKey(
            'fk_buyer_delivery_offer_manager_id',
            'buyer_delivery_offer',
            'manager_id',
            'user',
            'id',
        );
    }

    protected function buyerOfferImprovements()
    {
        $this->dropColumn('buyer_offer', 'price_packaging');
        $this->alterColumn(
            'buyer_offer',
            'price_product',
            $this->decimal(12, 4)->notNull(),
        );
        $this->alterColumn(
            'buyer_offer',
            'price_inspection',
            $this->decimal(12, 4)->notNull(),
        );
    }

    protected function createTypeDeliveryPriceTables()
    {
        $this->createTable('type_delivery_price', [
            'id' => $this->primaryKey(),
            'type_delivery_id' => $this->integer()->notNull(),
            'range_min' => $this->integer(),
            'range_max' => $this->integer(),
            'price' => $this->decimal(10, 4)
                ->notNull()
                ->defaultValue(0),
        ]);

        $this->addForeignKey(
            'fk-type_delivery_price-type_delivery_id',
            'type_delivery_price',
            'type_delivery_id',
            'type_delivery',
            'id',
        );

        // Admin linked tables
        $this->createTable('type_delivery_link_category', [
            'id' => $this->primaryKey(),
            'type_delivery_id' => $this->integer()->notNull(),
            'category_id' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-type_delivery_link_category-type_delivery_id',
            'type_delivery_link_category',
            'type_delivery_id',
            'type_delivery',
            'id',
        );

        $this->addForeignKey(
            'fk-type_delivery_link_category-category_id',
            'type_delivery_link_category',
            'category_id',
            'category',
            'id',
        );

        $this->createTable('type_delivery_link_subcategory', [
            'id' => $this->primaryKey(),
            'type_delivery_id' => $this->integer()->notNull(),
            'subcategory_id' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-type_delivery_link_subcategory-type_delivery_id',
            'type_delivery_link_subcategory',
            'type_delivery_id',
            'type_delivery',
            'id',
        );

        $this->addForeignKey(
            'fk-type_delivery_link_subcategory-subcategory_id',
            'type_delivery_link_subcategory',
            'subcategory_id',
            'subcategory',
            'id',
        );
    }

    protected function fillingTypeDeliveryPrices()
    {
        foreach (TypeDelivery::find()->each() as $typeDelivery) {
            $status = OrderDeliveryPriceService::addPriceRangeToTypeDelivery(
                $typeDelivery->id,
            );

            if (!$status->success) {
                echo 'Error pasting prices for type delivery: ' .
                    $typeDelivery->id .
                    PHP_EOL;
            }
        }
    }
}
