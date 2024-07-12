<?php

use yii\db\Migration;

/**
 * Class m230623_164153_remove_image_column_from_product_table
 */
class m230623_164153_remove_image_column_from_product_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('product', 'image');
    }

    public function safeDown()
    {
        $this->addColumn('product', 'image', 'LONGBLOB NOT NULL');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230623_164153_remove_image_column_from_product_table cannot be reverted.\n";

        return false;
    }
    */
}
