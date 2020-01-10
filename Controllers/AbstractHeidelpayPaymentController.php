<?php

declare(strict_types=1);

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
use RuntimeException;
use Shopware_Components_Snippet_Manager;
use Shopware_Controllers_Frontend_Payment;

abstract class AbstractHeidelpayPaymentController extends Shopware_Controllers_Frontend_Payment
{
    /** @var BasePaymentType */
    protected $paymentType;

    /** @var Heidelpay */
    protected $heidelpayClient;

    /** @var Enlight_Components_Session_Namespace */
    protected $session;

    /** @var bool */
    protected $isAsync;

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

        try {
            $this->heidelpayClient = $this->container->get('heidel_payment.services.api_client')->getHeidelpayClient();
        } catch (RuntimeException $ex) {
            $this->handleCommunicationError();

            return;
        }

        $this->customerHydrator         = $this->container->get('heidel_payment.resource_hydrator.customer');
        $this->businessCustomerHydrator = $this->container->get('heidel_payment.resource_hydrator.business_customer');
        $this->basketHydrator           = $this->container->get('heidel_payment.resource_hydrator.basket');
        $this->metadataHydrator         = $this->container->get('heidel_payment.resource_hydrator.metadata');

        $this->router  = $this->front->Router();
        $this->session = $this->container->get('session');

        $this->phpPrecision          = ini_get('precision');
        $this->phpSerializePrecision = ini_get('serialize_precision');

        ini_set('precision', '4');
        ini_set('serialize_precision', '4');

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
        ini_set('precision', $this->phpPrecision);
        ini_set('serialize_precision', $this->phpSerializePrecision);
    }

    protected function getHeidelpayB2cCustomer(): HeidelpayCustomer
    {
        $customer       = $this->getUser();
        $additionalData = $this->request->get('additional');

        if ($additionalData && array_key_exists('birthday', $additionalData)) {
            $customer['additional']['user']['birthday'] = $additionalData['birthday'];
        }

        return $this->customerHydrator->hydrateOrFetch($customer, $this->heidelpayClient);
    }

    protected function getHeidelpayB2bCustomer(): HeidelpayCustomer
    {
        $customer       = $this->getUser();
        $additionalData = $this->request->get('additional');

        if ($additionalData && array_key_exists('birthday', $additionalData)) {
            $customer['additional']['user']['birthday'] = $additionalData['birthday'];
        }

        return $this->businessCustomerHydrator->hydrateOrFetch($customer, $this->heidelpayClient);
    }

    protected function getHeidelpayBasket(): HeidelpayBasket
    {
        $basket = array_merge($this->getBasket(), [
            'sDispatch' => $this->session->get('sOrderVariables')['sDispatch'],
        ]);

        return $this->basketHydrator->hydrateOrFetch($basket, $this->heidelpayClient);
    }

    protected function getHeidelpayMetadata(): HeidelpayMetadata
    {
        $metadata = [
            'basketSignature' => $this->persistBasket(),
            'pluginVersion'   => $this->container->get('kernel')->getPlugins()['HeidelPayment']->getVersion(),
            'shopwareVersion' => $this->container->hasParameter('shopware.release.version') ? $this->container->getParameter('shopware.release.version') : 'unknown',
        ];

        return $this->metadataHydrator->hydrateOrFetch($metadata, $this->heidelpayClient);
    }

    protected function getHeidelpayReturnUrl(): string
    {
        return $this->router->assemble([
            'controller' => 'Heidelpay',
            'action'     => 'completePayment',
            'module'     => 'frontend',
        ]);
    }

    protected function getHeidelpayErrorUrl(string $message = ''): string
    {
        return $this->router->assemble([
            'controller'       => 'checkout',
            'action'           => 'shippingPayment',
            'module'           => 'frontend',
            'heidelpayMessage' => base64_encode($message),
        ]);
    }

    protected function getHeidelpayErrorUrlFromSnippet(string $namespace, string $snippetName): string
    {
        /** @var Shopware_Components_Snippet_Manager $snippetManager */
        $snippetManager = $this->container->get('snippets');
        $snippet        = $snippetManager->getNamespace($namespace)->get($snippetName);

        return $this->getHeidelpayErrorUrl($snippet);
    }

    protected function getApiLogger(): HeidelpayApiLoggerServiceInterface
    {
        return $this->container->get('heidel_payment.services.api_logger');
    }

    protected function handleCommunicationError(): void
    {
        $errorUrl = $this->getHeidelpayErrorUrlFromSnippet(
            'frontend/heidelpay/checkout/confirm',
            'communicationError'
        );

        if ($this->isAsync) {
            $this->view->assign(
                [
                    'success'     => false,
                    'redirectUrl' => $errorUrl,
                ]
            );

            return;
        }

        $this->redirect($errorUrl);
    }
}
