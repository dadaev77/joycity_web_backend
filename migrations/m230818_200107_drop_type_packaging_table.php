<?php

use yii\db\Migration;

/**
 * Handles the dropping of table `{{%type_packaging}}`.
 */
class m230818_200107_drop_type_packaging_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->dropForeignKey('fk_type_packaging_user1', 'type_packaging');

        $this->dropIndex('fk_type_packaging_user1_idx', 'type_packaging');

        $this->dropTable('type_packaging');
    }
}
