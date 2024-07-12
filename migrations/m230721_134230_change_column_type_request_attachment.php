<?php

use yii\db\Migration;

/**
 * Class m230721_134230_change_column_type_request_attachment
 */
class m230721_134230_change_column_type_request_attachment extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(
            'request_attachment',
            'file',
            $this->string(255)->notNull()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn(
            'request_attachment',
            'file',
            $this->binary()->notNull()
        );
    }
}
