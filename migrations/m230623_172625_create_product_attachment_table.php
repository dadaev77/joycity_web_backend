<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%product_attachment}}`.
 */
class m230623_172625_create_product_attachment_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `product_attachment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(255) NOT NULL,
  `file` LONGBLOB NOT NULL,
  PRIMARY KEY (`id`))');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('product_attachment');
    }
}
