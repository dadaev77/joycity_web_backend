<?php

use yii\db\Migration;

/**
 * Class m230911_155135_implementing_expected_order_price
 */
class m230911_155135_implementing_expected_order_price extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            'order',
            'quantity',
            $this->integer()
                ->notNull()
                ->defaultValue(0)
                ->after('price_delivery')
        );
        $this->renameColumn('order', 'quantity', 'total_quantity');

        $this->addColumn(
            'order',
            'expected_quantity',
            $this->integer()
                ->notNull()
                ->after('product_description')
        );
        $this->addColumn(
            'order',
            'expected_price_per_item',
            $this->float()
                ->notNull()
                ->after('expected_quantity')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230911_155135_implementing_expected_order_price cannot be reverted.\n";

        return false;
    }
}
