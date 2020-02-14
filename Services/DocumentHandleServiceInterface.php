<?php

namespace HeidelPayment\Services;

use HeidelPayment\Services\ViewBehaviorHandler\ViewBehaviorHandlerInterface;

interface DocumentHandleServiceInterface
{
    public function isDocumentCreatedByOrderId(
        int $orderId,
        int $typeId = ViewBehaviorHandlerInterface::DOCUMENT_TYPE_INVOICE
    ): bool;

    public function getDocumentIdByOrderId(
        int $orderId,
        int $typeId = ViewBehaviorHandlerInterface::DOCUMENT_TYPE_INVOICE
    ): int;
}
