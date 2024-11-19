<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%product_attachment_has_product}}`.
 */
class m230623_172756_create_product_attachment_has_product_table extends
    Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->createTable('product_attachment_has_product', [
            'id' => $this->primaryKey()
                ->notNull()
                ->append('AUTO_INCREMENT'),
            'product_attachment_id' => $this->integer()->notNull(),
            'product_id' => $this->integer()->notNull(),
            'type_use' => $this->string(255),
        ]);

        $this->createIndex(
            'fk_product_attachment_has_product_product1_idx',
            'product_attachment_has_product',
            'product_id'
        );
        $this->createIndex(
            'fk_product_attachment_has_product_product_attachment1_idx',
            'product_attachment_has_product',
            'product_attachment_id'
        );

        $this->addForeignKey(
            'fk_product_attachment_has_product_product_attachment1',
            'product_attachment_has_product',
            'product_attachment_id',
            'product_attachment',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            'fk_product_attachment_has_product_product1',
            'product_attachment_has_product',
            'product_id',
            'product',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }
}
