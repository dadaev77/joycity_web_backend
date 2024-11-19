<?php

use yii\db\Migration;

/**
 * Handles the dropping of table `{{%deep_inspection}}`.
 */
class m230818_201503_drop_deep_inspection_table extends Migration
{
    public function safeUp()
    {
        $this->dropForeignKey('fk_deep_inspection_user1', 'deep_inspection');

        $this->dropIndex('fk_deep_inspection_user1_idx', 'deep_inspection');

        $this->dropTable('deep_inspection');
    }
}
