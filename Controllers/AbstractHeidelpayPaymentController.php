<?php

namespace HeidelPayment\Controllers;

use Enlight_Components_Session_Namespace;
use Enlight_Controller_Router;
use HeidelPayment\Services\Heidelpay\DataProviderInterface;
use HeidelPayment\Services\HeidelpayApiLoggerServiceInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Basket as HeidelpayBasket;
use heidelpayPHP\Resources\Customer as HeidelpayCustomer;
use heidelpayPHP\Resources\Metadata as HeidelpayMetadata;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use Shopware_Controllers_Frontend_Payment;

abstract class AbstractHeidelpayPaymentController extends Shopware_Controllers_Frontend_Payment
{
    /** @var BasePaymentType */
    protected $paymentType;

    /** @var Heidelpay */
    protected $heidelpayClient;

    /** @var Enlight_Components_Session_Namespace */
    protected $session;

    /** @var DataProviderInterface */
    private $basketDataProvider;

    /** @var DataProviderInterface */
    private $customerDataProvider;

    /** @var DataProviderInterface */
    private $metadataDataProvider;

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

        $this->customerDataProvider = $this->container->get('heidel_payment.data_providers.customer');
        $this->basketDataProvider   = $this->container->get('heidel_payment.data_providers.basket');
        $this->metadataDataProvider = $this->container->get('heidel_payment.data_providers.metadata');
        $this->heidelpayClient      = $this->container->get('heidel_payment.services.api_client')->getHeidelpayClient();

        $this->router  = $this->front->Router();
        $this->session = $this->container->get('session');

        $this->phpPrecision          = ini_get('precision');
        $this->phpSerializePrecision = ini_get('serialize_precision');

        if (PHP_VERSION_ID >= 70100) {
            ini_set('precision', 17);
            ini_set('serialize_precision', -1);
        }

        $paymentTypeId = $this->request->get('resource') !== null ? $this->request->get('resource')['id'] : $this->request->get('typeId');

        try {
            $this->paymentType = $this->heidelpayClient->fetchPaymentType($paymentTypeId);
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException(sprintf('Error while fetching payment type by id [%s]', $paymentTypeId), $apiException);
        }
    }

    public function postDispatch()
    {
        if (PHP_VERSION_ID >= 70100) {
            ini_set('precision', $this->phpPrecision);
            ini_set('serialize_precision', $this->phpSerializePrecision);
        }
    }

    protected function getHeidelpayCustomer(): HeidelpayCustomer
    {
        $customer = $this->getUser();

        /** @var HeidelpayCustomer $heidelCustomer */
        return $this->customerDataProvider->hydrateOrFetch($customer, $this->heidelpayClient);
    }

    protected function getHeidelpayBasket(): HeidelpayBasket
    {
        $basket = array_merge($this->getBasket(), [
            'sDispatch' => $this->session->sOrderVariables['sDispatch'],
        ]);

        /** @var HeidelpayBasket $heidelpayBasket */
        return $this->basketDataProvider->hydrateOrFetch($basket, $this->heidelpayClient);
    }

    protected function getHeidelpayMetadata(): HeidelpayMetadata
    {
        $metadata = [
            'basketSignature' => $this->persistBasket(),
            'pluginVersion'   => $this->container->get('kernel')->getPlugins()['HeidelPayment']->getVersion(),
        ];

        /** @var HeidelpayMetadata $heidelMetadata */
        return $this->metadataDataProvider->hydrateOrFetch($metadata, $this->heidelpayClient);
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
