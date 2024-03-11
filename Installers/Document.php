<?php

declare(strict_types=1);

namespace UnzerPayment\Installers;

use Doctrine\DBAL\Connection;
use Shopware_Components_Translation;

class Document implements InstallerInterface
{
    private const INFO_NAME       = 'UnzerPayment_Info';
    private const INFO_TEMPLATE   = '/Assets/documents/%s/unzer_payment_info.tpl';
    private const FOOTER_NAME     = 'UnzerPayment_Footer';
    private const FOOTER_TEMPLATE = '/Assets/documents/%s/unzer_payment_footer.tpl';

    // TRANSLATION SPECIFIC
    private const VALUE_SUFFIX            = '_Value';
    private const GERMAN_PREFIX           = 'de_%';
    private const DOCUMENT_INVOICE_ID     = 1;
    private const TRANSLATION_OBJECT_TYPE = 'documents';

    private Connection $connection;

    private Shopware_Components_Translation $translationService;

    public function __construct(Connection $connection, Shopware_Components_Translation $translationService)
    {
        $this->connection         = $connection;
        $this->translationService = $translationService;
    }

    public function install(): void
    {
        $this->update('', '');

        if (!$this->templateExists(self::INFO_NAME)) {
            $infoTemplate = file_get_contents(__DIR__ . sprintf(self::INFO_TEMPLATE, 'de'));
            $this->connection->executeQuery(
                "INSERT INTO `s_core_documents_box` (`documentID`, `name`, `style`, `value`) VALUES (1, :infoName, '', :infoTemplate);",
                ['documentId' => self::DOCUMENT_INVOICE_ID, 'infoName' => self::INFO_NAME, 'infoTemplate' => $infoTemplate]);
            $translatedData[self::INFO_NAME . self::VALUE_SUFFIX] = file_get_contents(__DIR__ . sprintf(self::INFO_TEMPLATE, 'en'));
        }

        if (!$this->templateExists(self::FOOTER_NAME)) {
            $footerTemplate = file_get_contents(__DIR__ . sprintf(self::FOOTER_TEMPLATE, 'de'));
            $this->connection->executeQuery(
                "INSERT INTO `s_core_documents_box` (`documentID`, `name`, `style`, `value`) VALUES (1, :footerName, '', :footerTemplate);",
                ['documentId' => self::DOCUMENT_INVOICE_ID, 'footerName' => self::FOOTER_NAME, 'footerTemplate' => $footerTemplate]);
            $translatedData[self::FOOTER_NAME . self::VALUE_SUFFIX] = file_get_contents(__DIR__ . sprintf(self::FOOTER_TEMPLATE, 'en'));
        }

        if (!empty($translatedData)) {
            $this->installTranslation($translatedData);
        }
    }

    public function update(string $oldVersion, string $newVersion): void
    {
        if (!$this->templateExists(self::INFO_NAME) && $this->templateExists('HeidelPayment_Info')) {
            $this->connection->createQueryBuilder()
                ->update('s_core_documents_box')
                ->set('name', ':newName')
                ->where('name = :oldName')
                ->setParameter('newName', self::INFO_NAME)
                ->setParameter('oldName', 'HeidelPayment_Info')
                ->execute();
        }

        if (!$this->templateExists(self::FOOTER_NAME) && $this->templateExists('HeidelPayment_Footer')) {
            $this->connection->createQueryBuilder()
                ->update('s_core_documents_box')
                ->set('name', ':newName')
                ->where('name = :oldName')
                ->setParameter('newName', self::FOOTER_NAME)
                ->setParameter('oldName', 'HeidelPayment_Footer')
                ->execute();
        }
    }

    public function uninstall(): void
    {
        $sql = "DELETE FROM s_core_documents_box WHERE `name` LIKE 'UnzerPayment%'";
        $this->connection->exec($sql);
    }

    private function installTranslation(array $translations): void
    {
        $shopsToTranslate = $this->connection->createQueryBuilder()
            ->select('scs.id')
            ->from('s_core_shops', 'scs')
            ->innerJoin('scs', 's_core_locales', 'scl', 'scs.locale_id = scl.id')
            ->where('scl.locale NOT LIKE :germanLocalePrefix')
            ->setParameter('germanLocalePrefix', self::GERMAN_PREFIX)
            ->execute()->fetchAll();

        foreach ($shopsToTranslate as $shopId) {
            $this->translationService->write($shopId['id'], self::TRANSLATION_OBJECT_TYPE, 1, $translations, true);
        }
    }

    private function templateExists(string $templateName): bool
    {
        return $this->connection->createQueryBuilder()
            ->select('id')
            ->from('s_core_documents_box')
            ->where('documentID = :documentId')
            ->andWhere('name = :templateName')
            ->setParameter('documentId', self::DOCUMENT_INVOICE_ID)
            ->setParameter('templateName', $templateName)
            ->execute()->rowCount() >= 1;
    }
}
