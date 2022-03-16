<?php

declare(strict_types=1);

namespace UnzerPayment\Migration;

use Shopware\Components\Migrations\AbstractPluginMigration;

class Migration1647420783 extends AbstractPluginMigration
{
    public function up($modus): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE IF NOT EXISTS `s_plugin_unzer_order_ext_backup` (
                `unzer_order_id` varchar(50) NOT NULL,
                `payment_name` varchar(50) NOT NULL,
                `user_data` json NOT NULL,
                `basket_data` json NOT NULL,
                `s_comment` longtext NOT NULL,
                `dispatch_id` int(11) NOT NULL,
                `created_at` datetime NOT NULL
            );
SQL);
        // TODO: Implement up() method.
    }

    public function down(bool $keepUserData): void
    {
        $this->addSql(<<<SQL
            DROP TABLE IF EXISTS `s_plugin_unzer_order_ext_backup`;
SQL);
    }
}
