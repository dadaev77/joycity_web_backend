<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%app_option}}`.
 */
class m231014_194013_create_app_option_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('app_option', [
            'id' => $this->primaryKey(),
            'updated_at' => $this->dateTime(),
            'key' => $this->string(),
            'value' => $this->text(),
        ]);

        $this->createIndex('idx-app_option-key', 'app_option', 'key', true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%app_option}}');
    }
}
