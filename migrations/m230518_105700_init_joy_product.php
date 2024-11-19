<?php

use yii\db\Migration;

/**
 * Class m230518_105700_init_joy_product
 */
class m230518_105700_init_joy_product extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `category` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `en_name` VARCHAR(255) NOT NULL,
  `ru_name` VARCHAR(255) NOT NULL,
  `zh_name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`))
');
        $this->execute('CREATE TABLE IF NOT EXISTS `product` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `description` TEXT NOT NULL,
  `lot_size_price` DECIMAL NOT NULL,
  `image` LONGBLOB NOT NULL,
  `buyer_id` INT NOT NULL,
  `buyer_info` VARCHAR(45) NOT NULL,
  `lot_size` VARCHAR(45) NOT NULL,
  `review` VARCHAR(45) NULL,
  `rating` FLOAT NULL,
  `category_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `availability` INT NULL,
  `delivery_type` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_product_user1_idx` (`buyer_id` ASC) VISIBLE,
  INDEX `fk_product_category1_idx` (`category_id` ASC) VISIBLE,
  CONSTRAINT `fk_product_user1`
    FOREIGN KEY (`buyer_id`)
    REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_product_category1`
    FOREIGN KEY (`category_id`)
    REFERENCES `category` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)');
        $this->execute('CREATE TABLE IF NOT EXISTS `user_has_category` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  PRIMARY KEY (`id`, `user_id`, `category_id`),
  INDEX `fk_user_has_category_category1_idx` (`category_id` ASC) VISIBLE,
  INDEX `fk_user_has_category_user1_idx` (`user_id` ASC) VISIBLE,
  CONSTRAINT `fk_user_has_category_user1`
    FOREIGN KEY (`user_id`)
    REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_has_category_category1`
    FOREIGN KEY (`category_id`)
    REFERENCES `category` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute('DROP TABLE IF EXISTS `user_category`');
        $this->execute('DROP TABLE IF EXISTS `category`');
        $this->execute('DROP TABLE IF EXISTS `product`');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230518_105700_init_joy_product cannot be reverted.\n";

        return false;
    }
    */
}
