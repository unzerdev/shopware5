<?php

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Basket as HeidelpayBasket;
use heidelpayPHP\Resources\Metadata as HeidelpayMetadata;
use heidelpayPHP\Resources\PaymentTypes\Ideal;

class Shopware_Controllers_Widgets_HeidelpayIdeal extends Shopware_Controllers_Frontend_Payment
{
    public function createPaymentAction(): void
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        $resource = $this->request->get('resource');

        $basketProvider       = $this->container->get('heidel_payment.data_providers.basket');
        $metadataProvider     = $this->container->get('heidel_payment.data_providers.metadata');
        $customerDataProvider = $this->container->get('heidel_payment.data_providers.customer');
        $session              = $this->container->get('session');
        $heidelpayClient      = $this->container->get('heidel_payment.services.api_client')->getHeidelpayClient();

        $customer = $this->getUser();

        $basket = array_merge($this->getBasket(), [
            'sDispatch' => $session->sOrderVariables['sDispatch'],
        ]);

        $metadata = [
            'basketSignature' => $this->persistBasket(),
            'pluginVersion'   => $this->container->get('kernel')->getPlugins()['HeidelPayment']->getVersion(),
        ];

        //Fetch basket information
        /** @var HeidelpayBasket $heidelBasket */
        $heidelBasket = $basketProvider->hydrateOrFetch($basket, $heidelpayClient);

        //Fetch meta data
        /** @var HeidelpayMetadata $heidelMetadata */
        $heidelMetadata = $metadataProvider->hydrateOrFetch($metadata, $heidelpayClient);

        $heidelCustomer = $customerDataProvider->hydrateOrFetch($customer, $heidelpayClient);
        $returnUrl      = $this->front->Router()->assemble([
            'controller' => 'Heidelpay',
            'action'     => 'completePayment',
        ]);

        try {
            /** @var Ideal $idealPaymentType */
            $idealPaymentType = $heidelpayClient->fetchPaymentType($resource['id']);

            $result = $idealPaymentType->charge(
                $heidelBasket->getAmountTotal(),
                $heidelBasket->getCurrencyCode(),
                $returnUrl,
                $heidelCustomer,
                $heidelBasket->getOrderId(),
                $heidelMetadata,
                $heidelBasket,
                true
            );
        } catch (HeidelpayApiException $apiException) {
            $this->view->assign('redirectUrl', $this->front->Router()->assemble([
                'controller'       => 'checkout',
                'action'           => 'shippingPayment',
                'heidelpayMessage' => $apiException->getClientMessage(),
            ]));
        }

        $this->view->assign('success', isset($result));

        if (isset($result)) {
            $session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $this->view->assign('redirectUrl', $result->getPayment()->getRedirectUrl() ?: $returnUrl);
        }
    }
}
