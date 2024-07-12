<?php

use yii\db\Migration;

/**
 * Class m230625_125309_request_attachment_has_order_request
 */
class m230625_125309_request_attachment_has_order_request extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('
           CREATE TABLE IF NOT EXISTS `request_attachment_has_order_request` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `request_attachment_id` INT NOT NULL,
  `order_request_id` INT NOT NULL,
  PRIMARY KEY (`id`, `request_attachment_id`, `order_request_id`),
  INDEX `fk_request_attachment_has_order_request_order_request1_idx` (`order_request_id` ASC) VISIBLE,
  INDEX `fk_request_attachment_has_order_request_request_attachment1_idx` (`request_attachment_id` ASC) VISIBLE,
  CONSTRAINT `fk_request_attachment_has_order_request_request_attachment1`
    FOREIGN KEY (`request_attachment_id`)
    REFERENCES `request_attachment` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_request_attachment_has_order_request_order_request1`
    FOREIGN KEY (`order_request_id`)
    REFERENCES `order_request` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute('DROP TABLE IF EXISTS `request_attachment`');

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230625_125309_request_attachment_has_order_request cannot be reverted.\n";

        return false;
    }
    */
}
