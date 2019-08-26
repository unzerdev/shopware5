<?php

namespace HeidelPayment\Installers;

use Doctrine\DBAL\Connection;

class Mails implements InstallerInterface
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
        $mailExists = $this->connection->createQueryBuilder()->select('id')
            ->from('s_core_config_mails')
            ->where('name = :mailName')
            ->execute()->fetchColumn() > 0;

        if ($mailExists) {
            return;
        }

        $queryBuilder = $this->connection->createQueryBuilder();

        $contentPlain = file_get_contents(__DIR__ . '/Assets/Mail/PaymentReceived.txt');
        $contentHtml  = file_get_contents(__DIR__ . '/Assets/Mail/PaymentReceived.html');
        $context      = [
            'sOrder'  => 'Bestellung',
            'sShop'   => 'Shop',
            'sConfig' => 'Konfiguration',
            'amount'  => '10,00 EUR',
        ];

        $queryBuilder->insert('s_core_config_mails')
            ->values([
                'name'        => ':name',
                'frommail'    => ':fromMail',
                'fromname'    => ':fromName',
                'subject'     => ':subject',
                'content'     => ':contentPlain',
                'contentHTML' => ':contentHtml',
                'ishtml'      => ':isHtml',
                'mailtype'    => ':mailType',
                'context'     => ':context',
            ])->setParameters([
                'name'         => 'HeidelpayPaymentReceived',
                'fromMail'     => '{config name=mail}',
                'fromName'     => '{config name=shopName}',
                'subject'      => 'Zahlung erhalten',
                'contentPlain' => $contentPlain,
                'contentHtml'  => $contentHtml,
                'isHtml'       => true,
                'mailType'     => 2,
                'context'      => serialize($context),
            ])
            ->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $oldVersion, string $newVersion): void
    {
        // TODO: Implement update() method.
    }
}
