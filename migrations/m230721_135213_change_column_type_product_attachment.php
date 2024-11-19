<?php

use yii\db\Migration;

/**
 * Class m230721_135213_change_column_type_product_attachment
 */
class m230721_135213_change_column_type_product_attachment extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            'product_attachment',
            'file',
            $this->string(255)->notNull()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn(
            'product_attachment',
            'file',
            $this->binary()->notNull()
        );
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230721_135213_change_column_type_product_attachment cannot be reverted.\n";

        return false;
    }
    */
}
