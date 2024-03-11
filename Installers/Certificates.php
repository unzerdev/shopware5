<?php

declare(strict_types=1);

namespace UnzerPayment\Installers;

use Doctrine\DBAL\Connection;
use League\Flysystem\FilesystemInterface;
use UnzerPayment\Components\ApplePay\CertificateManager;

class Certificates implements InstallerInterface
{
    private Connection $connection;

    private FilesystemInterface $filesystem;

    private CertificateManager $certificateManager;

    public function __construct(Connection $connection, FilesystemInterface $filesystem)
    {
        $this->connection         = $connection;
        $this->filesystem         = $filesystem;
        $this->certificateManager = new CertificateManager($connection);
    }

    public function uninstall(): void
    {
        $shops = $this->connection->fetchAllAssociative('SELECT id FROM `s_core_shops`');

        foreach ($shops as $shop) {
            $shopId          = (int) $shop['id'];
            $certificatePath = $this->certificateManager->getMerchantIdentificationCertificatePath($shopId);

            if ($this->filesystem->has($certificatePath)) {
                $this->filesystem->delete($certificatePath);
            }

            $keyPath = $this->certificateManager->getMerchantIdentificationKeyPath($shopId);

            if ($this->filesystem->has($keyPath)) {
                $this->filesystem->delete($keyPath);
            }
        }
    }

    public function install(): void
    {
    }

    public function update(string $oldVersion, string $newVersion): void
    {
    }
}
