<?php

use yii\db\Migration;

/**
 * Class m250312_123935_remove_unique_order_id
 */
class m250312_123935_remove_unique_order_id extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Удаляем уникальность у поля order_id
        $this->alterColumn('order_distribution', 'order_id', $this->integer()->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Восстанавливаем уникальность (если нужно)
        $this->alterColumn('order_distribution', 'order_id', $this->integer()->notNull()->unique());
    }
}
