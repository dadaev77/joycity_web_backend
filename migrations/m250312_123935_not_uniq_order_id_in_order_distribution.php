<?php

use yii\db\Migration;

/**
 * Class m250312_123935_not_uniq_order_id_in_order_distribution
 */
class m250312_123935_not_uniq_order_id_in_order_distribution extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('order_distribution', 'order_id', $this->integer()->notNull()->unique(false));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250312_123935_not_uniq_order_id_in_order_distribution cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250312_123935_not_uniq_order_id_in_order_distribution cannot be reverted.\n";

        return false;
    }
    */
}
