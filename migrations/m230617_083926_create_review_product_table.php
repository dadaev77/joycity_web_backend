<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%review_product}}`.
 */
class m230617_083926_create_review_product_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('review_product', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'review' => $this->string(750)->notNull(),
            'rating' => $this->string(45)->notNull(),
            'customer_id' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'fk_review_product1_idx',
            'review_product',
            'product_id'
        );
        $this->createIndex(
            'fk_review_user1_idx',
            'review_product',
            'customer_id'
        );

        $this->addForeignKey(
            'fk_review_product1',
            'review_product',
            'product_id',
            'product',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            'fk_review_user1',
            'review_product',
            'customer_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_review_user1', 'review_product');
        $this->dropForeignKey('fk_review_product1', 'review_product');

        $this->dropIndex('fk_review_user1_idx', 'review_product');
        $this->dropIndex('fk_review_product1_idx', 'review_product');

        $this->dropTable('review_product');
    }
}
