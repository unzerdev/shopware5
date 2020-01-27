<?php

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;

class Shopware_Controllers_Widgets_HeidelpayCustomerData extends AbstractHeidelpayPaymentController
{
    public function getCustomerDataAction()
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        $session                  = $this->container->get('session');
        $userData                 = $session->offsetGet('sOrderVariables')['sUserData'];
        $customerHydrationService = $this->container->get('heidel_payment.resource_hydrator.business_customer');

        if (!empty($userData)) {
            $heidelpayCustomer = $customerHydrationService->hydrateOrFetch($userData);
        }

        $this->view->assign([
            'success'  => isset($heidelpayCustomer),
            'customer' => $heidelpayCustomer,
        ]);
    }
}
