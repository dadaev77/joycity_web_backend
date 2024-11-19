<?php

use yii\db\Migration;

/**
 * Class m230803_174451_add_subcategory_id_category_id_to_product_table
 */
class m230803_174451_add_subcategory_id_category_id_to_product_table extends
    Migration
{
    public function safeUp()
    {
        $this->addColumn(
            'product',
            'subcategory_id',
            $this->integer()
                ->notNull()
                ->after('lot_size_price_level_four')
        );
        $this->addColumn(
            'product',
            'category_id',
            $this->integer()
                ->notNull()
                ->after('lot_size_price_level_four')
        );

        $this->createIndex(
            'fk_product_subcategory1_idx',
            'product',
            'subcategory_id'
        );
        $this->addForeignKey(
            'fk_product_subcategory1',
            'product',
            'subcategory_id',
            'subcategory',
            'id',
            'NO ACTION',
            'NO ACTION'
        );

        $this->createIndex(
            'fk_product_category2_idx',
            'product',
            'category_id'
        );
        $this->addForeignKey(
            'fk_product_category2',
            'product',
            'category_id',
            'category',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }
}
