<?php

use yii\db\Migration;

/**
 * Class m250217_111559_update_order_table
 */
class m250217_111559_update_order_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('order', 'product_description_ru', $this->text()->after('product_name_ru'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250217_111559_update_order_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250217_111559_update_order_table cannot be reverted.\n";

        return false;
    }
    */
}
