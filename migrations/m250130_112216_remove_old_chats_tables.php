<?php

use yii\db\Migration;

/**
 * Class m250130_112216_remove_old_chats_tables
 */
class m250130_112216_remove_old_chats_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        if ($this->getDb()->schema->getTableSchema('{{%chat}}', true) !== null) {
            $this->dropTable('{{%chat}}');
        }
        if ($this->getDb()->schema->getTableSchema('{{%chat_translate}}', true) !== null) {
            $this->dropTable('{{%chat_translate}}');
        }
        if ($this->getDb()->schema->getTableSchema('{{%chat_user}}', true) !== null) {
            $this->dropTable('{{%chat_user}}');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250130_112216_remove_old_chats_tables cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250130_112216_remove_old_chats_tables cannot be reverted.\n";

        return false;
    }
    */
}
