<?php

use heidelpayPHP\Exceptions\HeidelpayApiException;

class Shopware_Controllers_Widgets_HeidelpayCustomerData extends Enlight_Controller_Action
{
    public function getCustomerDataAction()
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        $session = $this->container->get('session');
        $customerHydrationService = $this->container->get('heidel_payment.resource_hydrator.business_customer');

        $userData = $session->offsetGet('sOrderVariables')['sUserData'];
        $heidelpayClient = $this->container->get('heidel_payment.services.api_client')->getHeidelpayClient();

        $heidelpayCustomer = $customerHydrationService->hydrateOrFetch($userData, $heidelpayClient);

        $this->view->assign([
            'success' => true,
            'customer' => $heidelpayCustomer->expose()
        ]);
    }
}
