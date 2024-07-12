<?php

use yii\db\Migration;

/**
 * Class m230803_173521_remove_category_id_from_product_table
 */
class m230803_173521_remove_category_id_from_product_table extends Migration
{
    public function safeUp()
    {
        $this->dropForeignKey('fk_product_category1', 'product'); // Удаляем внешний ключ, если существует
        $this->dropIndex('fk_product_category1_idx', 'product'); // Удаляем индекс, если существует

        $this->dropColumn('product', 'category_id');
    }
}
