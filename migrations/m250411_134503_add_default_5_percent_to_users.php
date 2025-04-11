<?php

use yii\db\Migration;

/**
 * Class m250411_134503_add_default_5_percent_to_users
 */
class m250411_134503_add_default_5_percent_to_users extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%user}}', 'markup', $this->integer()->defaultValue(5));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250411_134503_add_default_5_percent_to_users cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250411_134503_add_default_5_percent_to_users cannot be reverted.\n";

        return false;
    }
    */
}
