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
        // Удаляем уникальный индекс с поля order_id
        $this->dropIndex('order_id', 'order_distribution');
        $this->dropIndex('order_id_2', 'order_distribution');
        $this->dropIndex('order_id_3', 'order_distribution');
        $this->dropIndex('fk_order_distribution_order_id', 'order_distribution');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Восстанавливаем уникальный индекс с поля order_id
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
