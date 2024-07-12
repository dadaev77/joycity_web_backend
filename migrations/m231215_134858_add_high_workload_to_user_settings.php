<?php

use yii\db\Migration;

/**
 * Class m231215_134858_add_high_workload_to_user_settings
 */
class m231215_134858_add_high_workload_to_user_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'user_settings',
            'high_workload',
            $this->integer()->defaultValue(0),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('user_settings', 'high_workload');
    }
}
