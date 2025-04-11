<?php

use yii\db\Migration;

/**
 * Class m250411_092248_add_telegram_id_to_user_table
 */
class m250411_092248_add_telegram_id_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'telegram_id', $this->string()->after('id'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250411_092248_add_telegram_id_to_user_table cannot be reverted.\n";
        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250411_092248_add_telegram_id_to_user_table cannot be reverted.\n";

        return false;
    }
    */
}
