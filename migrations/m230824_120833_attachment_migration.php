<?php

use yii\db\Migration;

/**
 * Class m230824_120833_attachment_migration
 */
class m230824_120833_attachment_migration extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('attachment', [
            'id' => $this->primaryKey(),
            'path' => $this->text()->notNull(),
            'size' => $this->integer()->notNull(),
            'extension' => $this->string()->notNull(),
            'mime_type' => $this->string()->notNull(),
        ]);

        // product link attachment
        $this->createTable('product_link_attachment', [
            'id' => $this->primaryKey(),
            'attachment_id' => $this->integer()->notNull(),
            'product_id' => $this->integer()->notNull(),
            'type' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_product_link_attachment_attachment_id',
            'product_link_attachment',
            'attachment_id',
            'attachment',
            'id'
        );
        $this->addForeignKey(
            'fk_product_link_attachment_product_id',
            'product_link_attachment',
            'product_id',
            'product',
            'id'
        );
        $this->dropTable('product_attachment_has_product');
        $this->dropTable('product_attachment');

        // feedback product
        $this->createTable('feedback_product', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'product_id' => $this->integer()->notNull(),
            'text' => $this->string(750)->notNull(),
            'rating' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_feedback_product_created_by',
            'feedback_product',
            'created_by',
            'user',
            'id'
        );
        $this->addForeignKey(
            'fk_feedback_product_product_id',
            'feedback_product',
            'product_id',
            'product',
            'id'
        );
        $this->dropTable('review_attachment_from_product_has_review_product');
        $this->dropTable('review_attachment_from_product');
        $this->dropTable('review_product');

        // feedback product link attachment
        $this->createTable('feedback_product_link_attachment', [
            'id' => $this->primaryKey(),
            'feedback_product_id' => $this->integer()->notNull(),
            'attachment_id' => $this->integer()->notNull(),
            'type' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_feedback_product_link_attachment_feedback_product_id',
            'feedback_product_link_attachment',
            'feedback_product_id',
            'feedback_product',
            'id'
        );
        $this->addForeignKey(
            'fk_feedback_product_link_attachment_attachment_id',
            'feedback_product_link_attachment',
            'attachment_id',
            'attachment',
            'id'
        );

        // feedback buyer
        $this->createTable('feedback_buyer', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'buyer_id' => $this->integer()->notNull(),
            'text' => $this->string(750)->notNull(),
            'rating' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_feedback_buyer_created_by',
            'feedback_buyer',
            'created_by',
            'user',
            'id'
        );
        $this->addForeignKey(
            'fk_feedback_buyer_buyer_id',
            'feedback_buyer',
            'buyer_id',
            'user',
            'id'
        );
        $this->dropTable('review_attachment_from_buyer_has_review_buyer');
        $this->dropTable('review_attachment_from_buyer');
        $this->dropTable('review_buyer');

        //    feedback product link attachment
        $this->createTable('feedback_buyer_link_attachment', [
            'id' => $this->primaryKey(),
            'feedback_buyer_id' => $this->integer()->notNull(),
            'attachment_id' => $this->integer()->notNull(),
            'type' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_feedback_buyer_link_attachment_feedback_buyer_id',
            'feedback_buyer_link_attachment',
            'feedback_buyer_id',
            'feedback_buyer',
            'id'
        );
        $this->addForeignKey(
            'fk_feedback_buyer_link_attachment_attachment_id',
            'feedback_buyer_link_attachment',
            'attachment_id',
            'attachment',
            'id'
        );

        // feedback user
        $this->createTable('feedback_user', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'text' => $this->string(750)->notNull(),
        ]);

        $this->addForeignKey(
            'fk_feedback_user_created_by',
            'feedback_user',
            'created_by',
            'user',
            'id'
        );
        $this->dropTable('feedback_attachment_has_feedback');
        $this->dropTable('feedback_attachment');
        $this->dropTable('feedback');

        // feedback user link attachment
        $this->createTable('feedback_user_link_attachment', [
            'id' => $this->primaryKey(),
            'feedback_user_id' => $this->integer()->notNull(),
            'attachment_id' => $this->integer()->notNull(),
            'type' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_feedback_user_link_attachment_feedback_user_id',
            'feedback_user_link_attachment',
            'feedback_user_id',
            'feedback_user',
            'id'
        );
        $this->addForeignKey(
            'fk_feedback_user_link_attachment_attachment_id',
            'feedback_user_link_attachment',
            'attachment_id',
            'attachment',
            'id'
        );

        // order request link attachment
        $this->createTable('order_request_link_attachment', [
            'id' => $this->primaryKey(),
            'order_request_id' => $this->integer()->notNull(),
            'attachment_id' => $this->integer()->notNull(),
            'type' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_order_request_link_attachment_order_request_id',
            'order_request_link_attachment',
            'order_request_id',
            'order_request',
            'id'
        );
        $this->addForeignKey(
            'fk_order_request_link_attachment_attachment_id',
            'order_request_link_attachment',
            'attachment_id',
            'attachment',
            'id'
        );
        $this->dropTable('request_attachment_has_order_request');
        $this->dropTable('request_attachment');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230824_120833_attachment_migration cannot be reverted.\n";

        return false;
    }
}
