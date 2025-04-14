<?php

use yii\db\Migration;

/**
 * Handles the creation of table `article`.
 */
class m240413_000002_create_article_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('article', [
            'id' => $this->primaryKey(),
            'product_id' => $this->integer()->notNull(),
            'colour_id' => $this->integer()->notNull(),
            'size' => $this->string(50)->notNull(),
            'count' => $this->integer()->notNull(),
            'image_link_colour' => $this->text(),
        ]);

        // Добавляем внешние ключи
        $this->addForeignKey(
            'fk-article-product_id',
            'article',
            'product_id',
            'product',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-article-colour_id',
            'article',
            'colour_id',
            'colour',
            'colour_id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-article-product_id', 'article');
        $this->dropForeignKey('fk-article-colour_id', 'article');
        $this->dropTable('article');
    }
} 