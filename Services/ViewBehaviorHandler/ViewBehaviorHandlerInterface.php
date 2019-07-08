<?php

namespace HeidelPayment\Services\ViewBehaviorHandler;

use Enlight_View_Default as View;

interface ViewBehaviorHandlerInterface
{
    public const ACTION_FINISH  = 'finish';
    public const ACTION_INVOICE = 'invoice';
    public const ACTION_EMAIL   = 'email';

    /**
     * @see ViewBehaviorHandlerInterface::ACTION_FINISH, ViewBehaviorHandlerInterface::ACTION_INVOICE, ViewBehaviorHandlerInterface::ACTION_EMAIL
     */
    public function execute(View $view, string $paymentId, string $action);
}
