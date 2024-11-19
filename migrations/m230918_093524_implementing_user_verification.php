<?php

use yii\db\Migration;

/**
 * Class m230918_093524_implementing_user_verification
 */
class m230918_093524_implementing_user_verification extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropTable('user_request_enabled');

        $this->dropColumn('user', 'enabled_delivery_type');
        $this->dropColumn('user', 'request_enabled');
        $this->dropColumn('user', 'links');

        $this->alterColumn(
            'user',
            'confirm_email',
            $this->boolean()
                ->notNull()
                ->defaultValue(0)
                ->after('is_deleted')
        );
        $this->renameColumn('user', 'confirm_email', 'is_email_confirmed');
        $this->alterColumn('user', 'role', $this->string()->notNull());
        $this->alterColumn(
            'user',
            'nickname',
            $this->string(100)->after('phone_number')
        );
        $this->alterColumn(
            'user',
            'access_token',
            $this->string()->after('password')
        );

        $this->addColumn(
            'user',
            'mpstats_token',
            $this->string(512)->after('avatar_id')
        );
        $this->addColumn(
            'user',
            'is_verified',
            $this->boolean()
                ->notNull()
                ->defaultValue(0)
                ->after('is_email_confirmed')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230918_093524_implementing_user_verification cannot be reverted.\n";

        return false;
    }
}
