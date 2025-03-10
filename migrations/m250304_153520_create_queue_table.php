<?php
use yii\db\Migration;

class m250304_153520_create_queue_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%queue}}', [
            'id' => $this->primaryKey(),
            'channel' => $this->string()->notNull(),
            'job' => $this->binary()->notNull(),
            'pushed_at' => $this->integer()->notNull(),
            'ttr' => $this->integer()->notNull(),
            'delay' => $this->integer()->notNull(),
            'priority' => $this->integer()->defaultValue(null),
            'reserved_at' => $this->integer()->defaultValue(null),
            'attempt' => $this->integer()->defaultValue(null),
            'done_at' => $this->integer()->defaultValue(null),
        ]);

        $this->createIndex('idx-queue-channel', '{{%queue}}', 'channel');
        $this->createIndex('idx-queue-reserved_at', '{{%queue}}', 'reserved_at');
        $this->createIndex('idx-queue-delay', '{{%queue}}', 'delay');
        $this->createIndex('idx-queue-priority', '{{%queue}}', 'priority');
    }

    public function safeDown()
    {
        $this->dropTable('{{%queue}}');
    }
}