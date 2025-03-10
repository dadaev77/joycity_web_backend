<?php

use yii\db\Migration;

/**
 * Class m250220_110231_add_delete_attrs
 */
class m250220_110231_add_delete_attrs extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%chats}}', 'deleted_at', $this->timestamp()->defaultValue(null));
        $this->addColumn('{{%chats}}', 'is_deleted', $this->boolean()->defaultValue(false));
        $this->addColumn('{{%messages}}', 'is_deleted', $this->boolean()->defaultValue(false));

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250220_110231_add_delete_attrs cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250220_110231_add_delete_attrs cannot be reverted.\n";

        return false;
    }
    */
}
