<?php

declare(strict_types=1);

namespace UnzerPayment\Installers;

use Doctrine\DBAL\Connection;

class Database implements InstallerInterface
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function install(): void
    {
        $this->update('', '');

        $sql = file_get_contents(__DIR__ . '/Assets/sql/install.sql');

        $this->connection->exec($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): void
    {
        $sql = file_get_contents(__DIR__ . '/Assets/sql/uninstall.sql');

        $this->connection->exec($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $oldVersion, string $newVersion): void
    {
        $oldTableName = $this->connection->fetchColumn('SHOW TABLES LIKE \'s_plugin_heidel_payment_vault\';');
        $newTableName = $this->connection->fetchColumn('SHOW TABLES LIKE \'s_plugin_unzer_payment_vault\';');

        if ($oldTableName !== false && !$newTableName) {
            $this->connection->exec('RENAME TABLE s_plugin_heidel_payment_vault TO s_plugin_unzer_payment_vault;');
        }

        $backupTableName = $this->connection->fetchColumn('SHOW TABLES LIKE \'s_plugin_unzer_order_ext_backup\';');

        if ($backupTableName !== false) {
            $this->connection->exec('ALTER TABLE s_plugin_unzer_order_ext_backup MODIFY `user_data` LONGTEXT;');
            $this->connection->exec('ALTER TABLE s_plugin_unzer_order_ext_backup MODIFY `basket_data` LONGTEXT;');
        }

        $sql = file_get_contents(__DIR__ . '/Assets/sql/update.sql');
        $this->connection->exec($sql);
    }
}
