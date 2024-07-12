<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%privacy_policy}}`.
 */
class m230803_074453_create_privacy_policy_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('privacy_policy', [
            'id' => $this->primaryKey(),
            'content' => $this->string(1055)->notNull(),
        ]);
    }
}
