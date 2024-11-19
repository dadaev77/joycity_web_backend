<?php

use yii\db\Migration;

/**
 * Class m230825_141804_product_refactoring
 */
class m230825_141804_product_refactoring extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropForeignKey('fk_product_category2', 'product');

        $this->dropColumn('product', 'buyer_info');
        $this->dropColumn('product', 'review');
        $this->dropColumn('product', 'availability');
        $this->dropColumn('product', 'delivery_type');
        $this->dropColumn('product', 'category_id');

        $this->dropColumn('product', 'lot_size_level_one_min');
        $this->dropColumn('product', 'lot_size_price_level_one');
        $this->dropColumn('product', 'lot_size_level_two_min');
        $this->dropColumn('product', 'lot_size_price_level_two');
        $this->dropColumn('product', 'lot_size_level_three_min');
        $this->dropColumn('product', 'lot_size_price_level_three');
        $this->dropColumn('product', 'lot_size_level_four_min');
        $this->dropColumn('product', 'lot_size_price_level_four');
        $this->dropColumn('product', 'lot_size_level_one_max');
        $this->dropColumn('product', 'lot_size_level_two_max');
        $this->dropColumn('product', 'lot_size_level_three_max');
        $this->dropColumn('product', 'lot_size_level_four_max');

        $this->alterColumn(
            'product',
            'name',
            $this->string()
                ->notNull()
                ->after('id')
        );
        $this->alterColumn(
            'product',
            'rating',
            $this->float()
                ->notNull()
                ->defaultValue(0)
                ->after('description')
        );

        $this->addColumn(
            'product',
            'range_1_min',
            $this->integer()
                ->notNull()
                ->defaultValue(1)
        );
        $this->addColumn('product', 'range_1_max', $this->integer()->notNull());
        $this->addColumn('product', 'range_1_price', $this->float()->notNull());
        $this->addColumn('product', 'range_2_min', $this->integer());
        $this->addColumn('product', 'range_2_max', $this->integer());
        $this->addColumn('product', 'range_2_price', $this->float());
        $this->addColumn('product', 'range_3_min', $this->integer());
        $this->addColumn('product', 'range_3_max', $this->integer());
        $this->addColumn('product', 'range_3_price', $this->float());
        $this->addColumn('product', 'range_4_min', $this->integer());
        $this->addColumn('product', 'range_4_max', $this->integer());
        $this->addColumn('product', 'range_4_price', $this->float());

        $this->addColumn(
            'product',
            'is_deleted',
            $this->boolean()
                ->notNull()
                ->defaultValue(0)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230825_141804_product_refactoring cannot be reverted.\n";

        return false;
    }
}
