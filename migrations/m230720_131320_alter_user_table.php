<?php

use yii\db\Migration;

/**
 * Class m230720_131320_alter_user_table
 */
class m230720_131320_alter_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Change column sizes
        $this->alterColumn('{{%user}}', 'email', $this->string(256)->null());
        $this->alterColumn('{{%user}}', 'country', $this->string(60)->null());
        $this->alterColumn('{{%user}}', 'city', $this->string(60)->null());
        $this->alterColumn('{{%user}}', 'address', $this->string(60)->null());
        $this->alterColumn(
            '{{%user}}',
            'phone_number',
            $this->string(15)->notNull()
        );
        $this->alterColumn(
            '{{%user}}',
            'confirm_email',
            $this->integer()->null()
        );

        // Remove unnecessary columns

        $this->dropColumn('{{%user}}', 'code');
        $this->dropColumn('{{%user}}', 'group');

        $this->addColumn(
            '{{%user}}',
            'enabled_delivery_type',
            $this->string(255)->null()
        );
    }

    public function safeDown()
    {
        // Revert the changes if needed (not implemented in this example).
        echo "This migration cannot be reverted.\n";
        return false;
    }
}
