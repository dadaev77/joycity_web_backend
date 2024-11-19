<?php

use yii\db\Migration;

/**
 * Class m230528_120745_init_joy_request
 */
class m230528_120745_init_joy_request extends Migration
{
    /**
     * {@inheritdoc}
     */

    public function safeUp()
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `order` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `status` VARCHAR(255) NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `product_description` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL NOT NULL,
  `delivery_type` VARCHAR(255) NOT NULL,
  `delivery_point_address` VARCHAR(255) NOT NULL,
  `package_type` VARCHAR(255) NOT NULL,
  `deep_inspection` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `buyer_id` INT NOT NULL,
  `inspection_report` VARCHAR(255) NULL,
  `in_stock_report` VARCHAR(255) NULL,
  `delivery_report` VARCHAR(255) NULL,
  `delivery_point_type` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_order_product1_idx` (`product_id` ASC) VISIBLE,
  INDEX `fk_order_user1_idx` (`customer_id` ASC) VISIBLE,
  INDEX `fk_order_user2_idx` (`buyer_id` ASC) VISIBLE,
  CONSTRAINT `fk_order_product1`
    FOREIGN KEY (`product_id`)
    REFERENCES `product` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_order_user1`
    FOREIGN KEY (`customer_id`)
    REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_order_user2`
    FOREIGN KEY (`buyer_id`)
    REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
        ');
        $this->execute('CREATE TABLE IF NOT EXISTS `order_request` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `status` VARCHAR(255) NOT NULL,
  `product_id` INT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `product_description` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL NOT NULL,
  `delivery_type` VARCHAR(255) NOT NULL,
  `delivery_point_type` VARCHAR(255) NOT NULL,
  `delivery_point_address` VARCHAR(255) NOT NULL,
  `package_type` VARCHAR(255) NULL,
  `deep_inspection` INT NULL,
  `order_id` INT NULL DEFAULT NULL,
  `buyer_id` INT NULL DEFAULT NULL,
  `customer_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_order_request_product1_idx` (`product_id` ASC) VISIBLE,
  INDEX `fk_order_request_order1_idx` (`order_id` ASC) VISIBLE,
  INDEX `fk_order_request_user1_idx` (`buyer_id` ASC) VISIBLE,
  INDEX `fk_order_request_user2_idx` (`customer_id` ASC) VISIBLE,
  CONSTRAINT `fk_order_request_product1`
    FOREIGN KEY (`product_id`)
    REFERENCES `product` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_order_request_order1`
    FOREIGN KEY (`order_id`)
    REFERENCES `order` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_order_request_user1`
    FOREIGN KEY (`buyer_id`)
    REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_order_request_user2`
    FOREIGN KEY (`customer_id`)
    REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)

        ');

        $this->execute('CREATE TABLE IF NOT EXISTS `buyer_offer` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `order_request_id` INT NOT NULL,
            `buyer_id` INT NOT NULL,
            `date_time` DATETIME NOT NULL,
            `quantity` INT NOT NULL,
            `price` DECIMAL NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `fk_buyer_offer_order_request1_idx` (`order_request_id` ASC) VISIBLE,
            INDEX `fk_buyer_offer_user1_idx` (`buyer_id` ASC) VISIBLE,
            CONSTRAINT `fk_buyer_offer_order_request1`
                FOREIGN KEY (`order_request_id`)
                REFERENCES `order_request` (`id`)
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
            CONSTRAINT `fk_buyer_offer_user1`
                FOREIGN KEY (`buyer_id`)
                REFERENCES `user` (`id`)
                ON DELETE NO ACTION
                ON UPDATE NO ACTION)
        ');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230528_120745_init_joy_request cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230528_120745_init_joy_request cannot be reverted.\n";

        return false;
    }
    */
}
