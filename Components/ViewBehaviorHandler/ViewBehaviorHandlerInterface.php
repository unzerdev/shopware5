<?php

declare(strict_types=1);

namespace HeidelPayment\Components\ViewBehaviorHandler;

use Enlight_View_Default as View;
use Smarty_Data;

interface ViewBehaviorHandlerInterface
{
    public const DOCUMENT_TYPE_INVOICE = 1;

    public function processCheckoutFinishBehavior(View $view, string $paymentId): void;

    /**
     * @see `s_core_documents`.`id` $documentType
     */
    public function processDocumentBehavior(Smarty_Data $viewAssignments, string $paymentId, int $documentType): void;

    public function processEmailVariablesBehavior(string $paymentId): array;
}
