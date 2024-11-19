<?php

use yii\db\Migration;

/**
 * Handles the dropping of table `{{%type_delivery}}`.
 */
class m230818_201058_drop_type_delivery_table extends Migration
{
    public function safeUp()
    {
        $this->dropForeignKey('fk_type_delivery_user1', 'type_delivery');

        $this->dropIndex('fk_type_delivery_user1_idx', 'type_delivery');

        $this->dropTable('type_delivery');
    }
}
