<?php

use yii\db\Migration;

/**
 * Class m230926_152329_add_avatar_to_category
 */
class m230926_152329_add_avatar_to_category extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('category', 'avatar_id', $this->integer()->notNull());
        $this->createIndex(
            'fk_category_attachment1_idx',
            'category',
            'avatar_id'
        );
        $this->addForeignKey(
            'fk_category_attachment1',
            'category',
            'avatar_id',
            'attachment',
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
        echo "m230926_152329_add_avatar_to_category cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230926_152329_add_avatar_to_category cannot be reverted.\n";

        return false;
    }
    */
}
