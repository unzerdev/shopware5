<?php

use HeidelPayment\Components\BookingMode;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Basket as HeidelpayBasket;
use heidelpayPHP\Resources\Metadata as HeidelpayMetadata;
use heidelpayPHP\Resources\PaymentTypes\Card as CreditCardType;

class Shopware_Controllers_Widgets_HeidelpayCreditCard extends Shopware_Controllers_Frontend_Payment
{
    public function createPaymentAction(): void
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        $creditResource     = $this->Request()->getPost('resource');
        $basketProvider     = $this->container->get('heidel_payment.data_providers.basket');
        $metadataProvider   = $this->container->get('heidel_payment.data_providers.metadata');
        $session            = $this->container->get('session');
        $heidelpayClient    = $this->container->get('heidel_payment.services.api_client')->getHeidelpayClient();
        $precision          = ini_get('precision');
        $serializePrecision = ini_get('serialize_precision');
        $bookingMode        = $this->container->get('heidel_payment.services.config_reader')->get('credit_card_bookingmode');

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

        $returnUrl = $this->front->Router()->assemble([
            'controller' => 'Heidelpay',
            'action'     => 'completePayment',
        ]);

        if (PHP_VERSION_ID >= 70100) {
            ini_set('precision', 17);
            ini_set('serialize_precision', -1);
        }

        try {
            /** @var CreditCardType $creditCardPayment */
            $creditCardPayment = $heidelpayClient->fetchPaymentType($creditResource['id']);

            if ($bookingMode === BookingMode::CHARGE || $bookingMode === BookingMode::CHARGE_REGISTER) {
                $result = $creditCardPayment->charge(
                    $heidelBasket->getAmountTotal(),
                    $heidelBasket->getCurrencyCode(),
                    $returnUrl, null,
                    $heidelBasket->getOrderId(),
                    $heidelMetadata,
                    $heidelBasket,
                    true
                );
            } else {
                $result = $creditCardPayment->authorize(
                    $heidelBasket->getAmountTotal(),
                    $heidelBasket->getCurrencyCode(),
                    $returnUrl,
                    null,
                    $heidelBasket->getOrderId(),
                    $heidelMetadata,
                    $heidelBasket,
                    true
                );
            }
        } catch (HeidelpayApiException $apiException) {
            $this->view->assign('redirectUrl', $this->front->Router()->assemble([
                'controller'       => 'checkout',
                'action'           => 'shippingPayment',
                'heidelpayMessage' => $apiException->getClientMessage(),
            ]));
        } finally {
            ini_set('precision', $precision);
            ini_set('serialize_precision', $serializePrecision);
        }

        $this->view->assign('success', isset($result));

        if (isset($result)) {
            $session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $this->view->assign('redirectUrl', $result->getPayment()->getRedirectUrl() ?: $returnUrl);
        }
    }
}
