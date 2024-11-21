<?php

use yii\db\Migration;

/**
 * Class m241121_103719_upd_order
 */
class m241121_103719_upd_order extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('order', 'amount_of_space', $this->integer()->null());
        $this->alterColumn('order', 'cargo_number', $this->string()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241121_103719_upd_order cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241121_103719_upd_order cannot be reverted.\n";

        return false;
    }
    */
}
