<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%product_inspection_report}}`.
 */
class m230914_150154_create_product_inspection_report_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('product_inspection_report', [
            'id' => $this->primaryKey(),
            'created_at' => $this->dateTime()->notNull(),
            'order_id' => $this->integer()->notNull(),
            'defects_count' => $this->integer()
                ->notNull()
                ->defaultValue(0),
            'package_state' => $this->string(255)->notNull(),
            'is_deep' => $this->tinyInteger()
                ->notNull()
                ->defaultValue(0),
        ]);

        $this->createIndex(
            'idx-product_inspection_report-order_id',
            'product_inspection_report',
            'order_id'
        );

        $this->addForeignKey(
            'fk-product_inspection_report-order_id',
            'product_inspection_report',
            'order_id',
            'order',
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
        $this->dropTable('{{%product_inspection_report}}');
    }
}
