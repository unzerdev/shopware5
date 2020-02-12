<?php

declare(strict_types=1);

namespace HeidelPayment\Installers;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware_Components_Translation;

class Document implements InstallerInterface
{
    private const INFO_NAME       = 'HeidelPayment_Info';
    private const INFO_TEMPLATE   = '/Assets/documents/%s/heidel_payment_info.tpl';
    private const FOOTER_NAME     = 'HeidelPayment_Footer';
    private const FOOTER_TEMPLATE = '/Assets/documents/%s/heidel_payment_footer.tpl';

//    TRANSLATION SPECIFIC
    private const VALUE_SUFFIX            = '_Value';
    private const GERMAN_PREFIX           = 'de_%';
    private const DOCUMENT_INVOICE_ID     = 1;
    private const TRANSLATION_OBJECT_TYPE = 'documents';

    /** @var Connection */
    private $connection;

    /** @var Shopware_Components_Translation */
    private $translationService;

    public function __construct(Connection $connection, Shopware_Components_Translation $translationService)
    {
        $this->connection         = $connection;
        $this->translationService = $translationService;
    }

    public function install(): void
    {
        if ($this->getCheckQuery()
            ->setParameter('templateName', self::INFO_NAME)
            ->execute()->rowCount() < 1) {
            $infoTemplate = file_get_contents(__DIR__ . sprintf(self::INFO_TEMPLATE, 'de'));
            $this->connection->executeQuery(
                "INSERT INTO `s_core_documents_box` (`documentID`, `name`, `style`, `value`) VALUES (1, :infoName, '', :infoTemplate);",
                ['infoName' => self::INFO_NAME, 'infoTemplate' => $infoTemplate]);
            $this->installTranslation(self::INFO_NAME, self::INFO_TEMPLATE);
        }

        if ($this->getCheckQuery()
                ->setParameter('templateName', self::FOOTER_NAME)
                ->execute()->rowCount() < 1) {
            $footerTemplate = file_get_contents(__DIR__ . sprintf(self::FOOTER_TEMPLATE, 'de'));
            $this->connection->executeQuery(
                "INSERT INTO `s_core_documents_box` (`documentID`, `name`, `style`, `value`) VALUES (1, :footerName, '', :footerTemplate);",
                ['footerName' => self::FOOTER_NAME, 'footerTemplate' => $footerTemplate]);
            $this->installTranslation(self::FOOTER_NAME, self::FOOTER_TEMPLATE);
        }

        $this->installTranslations();
    }

    public function update(string $oldVersion, string $newVersion): void
    {
        // Nothing to do here
    }

    public function uninstall(): void
    {
        $sql = "DELETE FROM s_core_documents_box WHERE `name` LIKE 'HeidelPayment%'";
        $this->connection->exec($sql);
    }

    private function installTranslation(string $dataName, string $filePath): void
    {
        $template = file_get_contents(__DIR__ . sprintf($filePath, 'en'));

        $shopsToTranslate = $this->connection->createQueryBuilder()
            ->select('scs.id')
            ->from('s_core_shops', 'scs')
            ->innerJoin('scs', 's_core_locales', 'scl', 'scs.locale_id = scl.id')
            ->where('scl.locale NOT LIKE :germanLocalePrefix')
            ->setParameter('germanLocalePrefix', self::GERMAN_PREFIX)
            ->execute()->fetchAll();

        $translations = [];
        foreach ($shopsToTranslate as $shopId) {
            $translations[] = [
                'objectdata' => [
                    $dataName . self::VALUE_SUFFIX => $template,
                ],
                'objectlanguage' => $shopId['id'],
                'objecttype'     => self::TRANSLATION_OBJECT_TYPE,
                'objectkey'      => self::DOCUMENT_INVOICE_ID,
            ];
        }

        $this->translationService->writeBatch($translations, true);
    }

    private function getCheckQuery(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('id')
            ->from('s_core_documents_box')
            ->where('name = :templateName');
    }
}
