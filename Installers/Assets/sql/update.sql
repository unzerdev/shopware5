CREATE TABLE IF NOT EXISTS `s_plugin_unzer_order_ext_backup` (
    `unzer_order_id` varchar(50) NOT NULL,
    `payment_name` varchar(50) NOT NULL,
    `user_data` LONGTEXT NOT NULL,
    `basket_data` LONGTEXT NOT NULL,
    `s_comment` longtext NULL,
    `dispatch_id` int(11) NOT NULL,
    `created_at` datetime NOT NULL ON UPDATE now()
);
