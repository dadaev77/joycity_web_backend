<?php

use yii\db\Migration;

/**
 * Class m230512_122808_init_joy_user_structucture
 */
class m230512_122808_init_joy_user_structucture extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NULL,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(60) NULL,
  `surname` VARCHAR(60) NULL,
  `organization_name` VARCHAR(60) NULL,
  `phone_number` INT(12) NULL,
  `links` VARCHAR(255) NULL,
  `country` VARCHAR(255) NULL,
  `city` VARCHAR(255) NULL,
  `address` VARCHAR(255) NULL,
  `role` VARCHAR(255) NULL,
  `group` VARCHAR(255) NULL,
  `nickname` VARCHAR(255) NULL,
  `access_token` VARCHAR(255) NULL,
  `code` INT(4) NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `nickname_UNIQUE` (`nickname` ASC) VISIBLE,
  UNIQUE INDEX `phone_number_UNIQUE` (`phone_number` ASC) VISIBLE,
  UNIQUE INDEX `email_UNIQUE` (`email` ASC) VISIBLE)
');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230512_122808_init_joy_user_structucture cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230512_122808_init_joy_user_structucture cannot be reverted.\n";

        return false;
    }
    */
}
