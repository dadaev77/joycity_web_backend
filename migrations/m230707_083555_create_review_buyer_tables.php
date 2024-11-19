<?php

use yii\db\Migration;

/**
 * Class m230707_083555_create_review_buyer_tables
 */
class m230707_083555_create_review_buyer_tables extends Migration
{
    public function up()
    {
        // Создание таблицы `review_buyer`
        $this->createTable('review_buyer', [
            'id' => $this->primaryKey()
                ->notNull()
                ->append('AUTO_INCREMENT'),
            'review' => $this->string(750)->notNull(),
            'rating' => $this->string(45)->notNull(),
            'publication_date' => $this->date()->notNull(),
            'buyer_id' => $this->integer()->notNull(),
            'customer_id' => $this->integer()->notNull(),
        ]);

        // Создание индексов для внешних ключей
        $this->createIndex(
            'fk_review_buyer_user2_idx',
            'review_buyer',
            'buyer_id'
        );
        $this->createIndex(
            'fk_review_buyer_user1_idx',
            'review_buyer',
            'customer_id'
        );

        // Создание внешних ключей
        $this->addForeignKey(
            'fk_review_buyer_user2',
            'review_buyer',
            'buyer_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            'fk_review_buyer_user1',
            'review_buyer',
            'customer_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION'
        );

        // Создание таблицы `review_attachment_from_buyer`
        $this->createTable('review_attachment_from_buyer', [
            'id' => $this->primaryKey()
                ->notNull()
                ->append('AUTO_INCREMENT'),
            'type' => $this->string(255)->notNull(),
            'file' => $this->string(255)->notNull(),
        ]);

        // Создание таблицы `review_attachment_from_buyer_has_review_buyer`
        $this->createTable('review_attachment_from_buyer_has_review_buyer', [
            'id' => $this->primaryKey()
                ->notNull()
                ->append('AUTO_INCREMENT'),
            'review_attachment_from_buyer_id' => $this->integer()->notNull(),
            'review_buyer_id' => $this->integer()->notNull(),
        ]);

        // Создание индексов для внешних ключей
        $this->createIndex(
            'fk_review_attachment_from_buyer_has_review_buyer_review_buy_idx',
            'review_attachment_from_buyer_has_review_buyer',
            'review_buyer_id'
        );
        $this->createIndex(
            'fk_review_attachment_from_buyer_has_review_buyer_review_att_idx',
            'review_attachment_from_buyer_has_review_buyer',
            'review_attachment_from_buyer_id'
        );

        // Создание внешних ключей
        $this->addForeignKey(
            'fk_review_attachment_from_buyer_has_review_buyer_review_attac1',
            'review_attachment_from_buyer_has_review_buyer',
            'review_attachment_from_buyer_id',
            'review_attachment_from_buyer',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            'fk_review_attachment_from_buyer_has_review_buyer_review_buyer1',
            'review_attachment_from_buyer_has_review_buyer',
            'review_buyer_id',
            'review_buyer',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }
}
