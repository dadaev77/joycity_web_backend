<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%attachment}}`.
 */
class m240926_172228_add_new_column_to_attachment_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('attachment', 'img_size', $this->string());
    }
    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('attachment', 'img_size');
    }
}
