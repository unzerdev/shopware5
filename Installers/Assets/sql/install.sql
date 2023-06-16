CREATE TABLE IF NOT EXISTS `s_plugin_unzer_payment_vault` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `device_type` VARCHAR(16) NOT NULL,
    `type_id` VARCHAR(32) NOT NULL,
    `data` TEXT NOT NULL,
    `date` DATETIME NOT NULL,
    `address_hash` VARCHAR(32) NOT NULL,
     PRIMARY KEY (`id`),
     UNIQUE (`type_id`)
)
DEFAULT CHARSET = utf8
COLLATE = utf8_unicode_ci
ENGINE = InnoDB;


CREATE TABLE IF NOT EXISTS `s_plugin_unzer_apple_pay_configuration` (
    `shop_id` int(11) NOT NULL PRIMARY KEY,
    `payment_certificate_id` varchar(50) NULL,
    `merchant_certificate_id` varchar(50) NULL,
    `last_updated_at` datetime NOT NULL ON UPDATE now()
    );
