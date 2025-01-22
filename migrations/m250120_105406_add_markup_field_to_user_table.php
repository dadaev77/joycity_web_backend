<?php

use yii\db\Migration;

/**
 * Class m250120_105406_add_markup_field_to_user_table
 */
class m250120_105406_add_markup_field_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'markup', $this->integer()->defaultValue(null));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250120_105406_add_markup_field_to_user_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250120_105406_add_markup_field_to_user_table cannot be reverted.\n";

        return false;
    }
    */
}
