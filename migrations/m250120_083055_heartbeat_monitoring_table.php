<?php

use yii\db\Migration;

/**
 * Class m250120_083055_heartbeat_monitoring_table
 */
class m250120_083055_heartbeat_monitoring_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('heartbeat', [
            'id' => $this->primaryKey(),
            'service_name' => $this->string()->notNull(),
            'last_run_at' => $this->dateTime()->notNull(),
            'status' => $this->string()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('heartbeat');
    }
}
