<?php

namespace HeidelPayment\Controllers;

use Enlight_Components_Session_Namespace;
use Enlight_Controller_Router;
use HeidelPayment\Services\Heidelpay\HeidelpayResourceHydratorInterface;
use HeidelPayment\Services\HeidelpayApiLoggerServiceInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Basket as HeidelpayBasket;
use heidelpayPHP\Resources\Customer as HeidelpayCustomer;
use heidelpayPHP\Resources\Metadata as HeidelpayMetadata;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use Shopware;
use Shopware_Controllers_Frontend_Payment;

abstract class AbstractHeidelpayPaymentController extends Shopware_Controllers_Frontend_Payment
{
    /** @var BasePaymentType */
    protected $paymentType;

    /** @var Heidelpay */
    protected $heidelpayClient;

    /** @var Enlight_Components_Session_Namespace */
    protected $session;

    /** @var HeidelpayResourceHydratorInterface */
    private $basketHydrator;

    /** @var HeidelpayResourceHydratorInterface */
    private $customerHydrator;

    /** @var HeidelpayResourceHydratorInterface */
    private $businessCustomerHydrator;

    /** @var HeidelpayResourceHydratorInterface */
    private $metadataHydrator;

    /** @var Enlight_Controller_Router */
    private $router;

    /** @var int */
    private $phpPrecision;

    /** @var int */
    private $phpSerializePrecision;

    /**
     * {@inheritdoc}
     */
    public function preDispatch()
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        $this->customerHydrator         = $this->container->get('heidel_payment.resource_hydrator.customer');
        $this->businessCustomerHydrator = $this->container->get('heidel_payment.resource_hydrator.business_customer');
        $this->basketHydrator           = $this->container->get('heidel_payment.resource_hydrator.basket');
        $this->metadataHydrator         = $this->container->get('heidel_payment.resource_hydrator.metadata');
        $this->heidelpayClient          = $this->container->get('heidel_payment.services.api_client')->getHeidelpayClient();

        $this->router  = $this->front->Router();
        $this->session = $this->container->get('session');

        $this->phpPrecision          = ini_get('precision');
        $this->phpSerializePrecision = ini_get('serialize_precision');

        if (PHP_VERSION_ID >= 70100) {
            ini_set('precision', 17);
            ini_set('serialize_precision', -1);
        }

        $paymentTypeId = $this->request->get('resource') !== null ? $this->request->get('resource')['id'] : $this->request->get('typeId');

        if ($paymentTypeId) {
            try {
                $this->paymentType = $this->heidelpayClient->fetchPaymentType($paymentTypeId);
            } catch (HeidelpayApiException $apiException) {
                $this->getApiLogger()->logException(
                    sprintf('Error while fetching payment type by id [%s]', $paymentTypeId),
                    $apiException
                );
            }
        }
    }

    public function postDispatch()
    {
        if (PHP_VERSION_ID >= 70100) {
            ini_set('precision', $this->phpPrecision);
            ini_set('serialize_precision', $this->phpSerializePrecision);
        }
    }

    protected function getHeidelpayB2cCustomer(): HeidelpayCustomer
    {
        $customer = $this->getUser();

        /** @var HeidelpayCustomer $heidelCustomer */
        return $this->customerHydrator->hydrateOrFetch($customer, $this->heidelpayClient);
    }

    protected function getHeidelpayB2bCustomer(): HeidelpayCustomer
    {
        $customer = $this->getUser();

        return $this->businessCustomerHydrator->hydrateOrFetch($customer, $this->heidelpayClient);
    }

    protected function getHeidelpayBasket(): HeidelpayBasket
    {
        $basket = array_merge($this->getBasket(), [
            'sDispatch' => $this->session->sOrderVariables['sDispatch'],
        ]);

        /** @var HeidelpayBasket $heidelpayBasket */
        return $this->basketHydrator->hydrateOrFetch($basket, $this->heidelpayClient);
    }

    protected function getHeidelpayMetadata(): HeidelpayMetadata
    {
        $metadata = [
            'basketSignature' => $this->persistBasket(),
            'pluginVersion'   => $this->container->get('kernel')->getPlugins()['HeidelPayment']->getVersion(),
            'shopwareVersion' => $this->container->hasParameter('shopware.release.version') ? $this->container->getParameter('shopware.release.version') : Shopware::VERSION,
        ];

        /** @var HeidelpayMetadata $heidelMetadata */
        return $this->metadataHydrator->hydrateOrFetch($metadata, $this->heidelpayClient);
    }

    protected function getHeidelpayReturnUrl(): string
    {
        return $this->router->assemble([
            'controller' => 'Heidelpay',
            'action'     => 'completePayment',
        ]);
    }

    protected function getHeidelpayErrorUrl(string $message = ''): string
    {
        return $this->front->Router()->assemble([
            'controller'       => 'checkout',
            'action'           => 'shippingPayment',
            'heidelpayMessage' => base64_encode($message),
        ]);
    }

    protected function getApiLogger(): HeidelpayApiLoggerServiceInterface
    {
        return $this->container->get('heidel_payment.services.api_logger');
    }
}
