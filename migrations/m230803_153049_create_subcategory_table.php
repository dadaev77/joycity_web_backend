<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%subcategory}}`.
 */
class m230803_153049_create_subcategory_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('subcategory', [
            'id' => $this->primaryKey(),
            'en_name' => $this->string(255)->notNull(),
            'ru_name' => $this->string(255)->notNull(),
            'zh_name' => $this->string(255)->notNull(),
            'category_id' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'fk_subcategory_category1_idx',
            'subcategory',
            'category_id'
        );
        $this->addForeignKey(
            'fk_subcategory_category1',
            'subcategory',
            'category_id',
            'category',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }
}
