<?php

use yii\db\Migration;

/**
 * Class m230815_153725_change_product_table
 */
class m230815_153725_change_product_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            'product',
            'lot_size_level_one',
            $this->integer()
                ->notNull()
                ->defaultValue(1)
        );
        $this->alterColumn('product', 'lot_size_level_two', $this->integer());
        $this->alterColumn('product', 'lot_size_level_three', $this->integer());
        $this->alterColumn('product', 'lot_size_level_four', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230815_153725_change_product_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230815_153725_change_product_table cannot be reverted.\n";

        return false;
    }
    */
}
