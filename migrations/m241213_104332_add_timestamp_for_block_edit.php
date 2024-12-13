<?php

use yii\db\Migration;

/**
 * Class m241213_104332_add_timestamp_for_block_edit
 */
class m241213_104332_add_timestamp_for_block_edit extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%waybill}}', 'block_edit_date', $this->timestamp()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241213_104332_add_timestamp_for_block_edit cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241213_104332_add_timestamp_for_block_edit cannot be reverted.\n";

        return false;
    }
    */
}
