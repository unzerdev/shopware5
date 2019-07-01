CREATE TABLE IF NOT EXISTS `s_plugin_heidel_payment_vault` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `user_id` INT NOT NULL ,
    `device_type` INT NOT NULL ,
    `type_id` VARCHAR(32) NOT NULL ,
    `data` TEXT NOT NULL ,
    `date` DATE NOT NULL ,
     PRIMARY KEY (`id`),
     UNIQUE (`type_id`)
) ENGINE = InnoDB;
