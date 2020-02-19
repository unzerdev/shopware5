<?php

declare(strict_types=1);

namespace HeidelPayment\Services\DocumentHandler;

use Shopware\Models\Order\Document\Document;

interface DocumentHandlerServiceInterface
{
    public function isInvoiceCreatedByOrderId(int $orderId): bool;

    public function getInvoiceDocumentByOrderId(int $orderId): ?Document;
}
