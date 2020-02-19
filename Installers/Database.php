<?php

declare(strict_types=1);

namespace HeidelPayment\Installers;

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
    }
}
