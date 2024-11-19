<?php

use yii\db\Migration;

/**
 * Handles the dropping of table `{{%type_destination}}`.
 */
class m230818_200534_drop_type_destination_table extends Migration
{
    public function safeUp()
    {
        // Удаляем внешний ключ
        $this->dropForeignKey('fk_type_destination_user1', 'type_destination');

        // Удаляем таблицу
        $this->dropTable('type_destination');
    }
}
