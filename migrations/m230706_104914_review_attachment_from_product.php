<?php

use yii\db\Migration;

/**
 * Class m230706_104914_review_attachment_from_product
 */
class m230706_104914_review_attachment_from_product extends Migration
{
    public function up()
    {
        $this->createTable('review_attachment_from_product', [
            'id' => $this->primaryKey()
                ->notNull()
                ->append('AUTO_INCREMENT'),
            'type' => $this->string(255)->notNull(),
            'file' => $this->string(255)->notNull(),
        ]);

        $this->createTable(
            'review_attachment_from_product_has_review_product',
            [
                'id' => $this->primaryKey()
                    ->notNull()
                    ->append('AUTO_INCREMENT'),
                'review_attachment_from_product_id' => $this->integer()->notNull(),
                'review_product_id' => $this->integer()->notNull(),
                'type_use' => $this->string(255),
            ]
        );

        $this->createIndex(
            'fk_review_attachment_from_product_has_review_product_review_idx',
            'review_attachment_from_product_has_review_product',
            'review_product_id'
        );
        $this->createIndex(
            'fk_review_attachment_from_product_has_review_product_review_idx1',
            'review_attachment_from_product_has_review_product',
            'review_attachment_from_product_id'
        );

        $this->addForeignKey(
            'fk_review_attachment_from_product_has_review_product_review_a1',
            'review_attachment_from_product_has_review_product',
            'review_attachment_from_product_id',
            'review_attachment_from_product',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            'fk_review_attachment_from_product_has_review_product_review_p1',
            'review_attachment_from_product_has_review_product',
            'review_product_id',
            'review_product',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }
}
