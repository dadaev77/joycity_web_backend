<?php

use yii\db\Migration;

/**
 * Class m241008_104900_update_name_desc_fields_prod_ord_tables
 */
class m241008_104900_update_name_desc_fields_prod_ord_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameColumn('product', 'name', 'name_ru');
        $this->renameColumn('product', 'description', 'description_ru');
        $this->renameColumn('order', 'product_name', 'product_name_ru');
        $this->renameColumn('order', 'product_description', 'product_description_ru');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241008_104900_update_name_desc_fields_prod_ord_tables cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241008_104900_update_name_desc_fields_prod_ord_tables cannot be reverted.\n";

        return false;
    }
    */
}
