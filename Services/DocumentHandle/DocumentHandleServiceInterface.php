<?php

declare(strict_types=1);

namespace HeidelPayment\Services\DocumentHandle;

use Shopware\Models\Order\Document\Document;

interface DocumentHandleServiceInterface
{
    public function isInvoiceCreatedByTransactionId(int $orderId): bool;

    public function getInvoiceDocumentByOrderId(int $orderId): ?Document;
}
