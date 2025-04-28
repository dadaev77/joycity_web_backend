<?php

use yii\db\Migration;

/**
 * Class m250428_063925_remove_unused_tables
 */
class m250428_063925_remove_unused_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropTable('{{%article}}');
        $this->dropTable('{{%colour}}');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250428_063925_remove_unused_tables cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250428_063925_remove_unused_tables cannot be reverted.\n";

        return false;
    }
    */
}
