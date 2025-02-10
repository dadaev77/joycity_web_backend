<?php

use yii\db\Migration;

/**
 * Class m250210_120726_upd_user_verify_table
 */
class m250210_120726_upd_user_verify_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user_verification_request', 'is_read', $this->boolean()->defaultValue(false));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('user_verification_request', 'is_read');
    }
}
