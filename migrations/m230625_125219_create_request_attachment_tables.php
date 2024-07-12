<?php

use yii\db\Migration;

/**
 * Class m230625_125219_create_request_attachment_tables
 */
class m230625_125219_create_request_attachment_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('
            CREATE TABLE IF NOT EXISTS `request_attachment` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `type` VARCHAR(255) NOT NULL,
              `file` LONGBLOB NOT NULL,
              PRIMARY KEY (`id`)
            )
        ');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute(
            'DROP TABLE IF EXISTS `request_attachment_has_order_request`'
        );

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230625_125219_create_request_attachment_tables cannot be reverted.\n";

        return false;
    }
    */
}
