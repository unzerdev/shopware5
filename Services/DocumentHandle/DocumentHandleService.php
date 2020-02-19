<?php

declare(strict_types=1);

namespace HeidelPayment\Services\DocumentHandle;

use Exception;
use HeidelPayment\Components\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use Psr\Log\LoggerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Document\Document;

class DocumentHandleService implements DocumentHandleServiceInterface
{
    /** @var ModelManager */
    private $modelManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ModelManager $modelManager, LoggerInterface $logger)
    {
        $this->modelManager = $modelManager;
        $this->logger       = $logger;
    }

    public function isInvoiceCreatedByTransactionId(int $orderId): bool
    {
        try {
            return $this->modelManager->getConnection()->createQueryBuilder()->select('id')
                    ->from('s_order_documents')
                    ->where('orderId = :orderId')
                    ->andWhere('type = :invoiceType')
                    ->setParameter('orderId', $orderId)
                    ->setParameter('invoiceType', ViewBehaviorHandlerInterface::DOCUMENT_TYPE_INVOICE)
                    ->execute()->rowCount() > 0;
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage(), $ex->getTrace());

            return false;
        }
    }

    public function getInvoiceDocumentByOrderId(int $orderId): ?Document
    {
        return $this->modelManager->getRepository(Document::class)->findOneBy([
            'orderId' => $orderId,
            'typeId'  => ViewBehaviorHandlerInterface::DOCUMENT_TYPE_INVOICE,
        ]);
    }
}
