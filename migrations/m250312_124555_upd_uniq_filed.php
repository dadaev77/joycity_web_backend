<?php

use yii\db\Migration;

/**
 * Class m250312_124555_upd_uniq_filed
 */
class m250312_124555_upd_uniq_filed extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropIndex('fk_order_distribution_order_id', 'order_distribution');
        $this->alterColumn('order_distribution', 'order_id', $this->integer()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

        $this->createIndex('order_id', 'order_distribution', 'order_id', true);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250312_124555_upd_uniq_filed cannot be reverted.\n";

        return false;
    }
    */
}
