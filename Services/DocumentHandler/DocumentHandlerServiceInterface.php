<?php

declare(strict_types=1);

namespace UnzerPayment\Services\DocumentHandler;

use UnzerPayment\Components\ViewBehaviorHandler\ViewBehaviorHandlerInterface;

interface DocumentHandlerServiceInterface
{
    public function isDocumentCreatedByOrderId(int $orderId, int $invoiceType = ViewBehaviorHandlerInterface::DOCUMENT_TYPE_INVOICE): bool;

    public function getDocumentIdByOrderId(int $orderId, int $invoiceType = ViewBehaviorHandlerInterface::DOCUMENT_TYPE_INVOICE): int;
}
