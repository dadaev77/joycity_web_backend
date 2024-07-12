<?php

use yii\db\Migration;

/**
 * Class m230808_215905_add_subcategory_category_to_order_request
 */
class m230808_215905_add_subcategory_category_to_order_request extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            'order_request',
            'subcategory_id',
            $this->integer()->notNull()
        );
        $this->addColumn(
            'order_request',
            'category_id',
            $this->integer()->notNull()
        );

        $this->createIndex(
            'fk_order_request_subcategory1_idx',
            'order_request',
            'subcategory_id'
        );
        $this->createIndex(
            'fk_order_request_category1_idx',
            'order_request',
            'category_id'
        );

        $this->addForeignKey(
            'fk_order_request_subcategory1',
            'order_request',
            'subcategory_id',
            'subcategory',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            'fk_order_request_category1',
            'order_request',
            'category_id',
            'category',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }
}
