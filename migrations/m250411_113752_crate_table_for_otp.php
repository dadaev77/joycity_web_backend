<?php

use yii\db\Migration;

/**
 * Class m250411_113752_crate_table_for_otp
 */
class m250411_113752_crate_table_for_otp extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%otp}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->bigInteger()->notNull(),
            'telegram_id' => $this->bigInteger()->notNull(),
            'otp_code' => $this->string(6)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'expires_at' => $this->dateTime()->notNull(),
            'is_used' => $this->boolean()->defaultValue(false),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250411_113752_crate_table_for_otp cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250411_113752_crate_table_for_otp cannot be reverted.\n";

        return false;
    }
    */
}
