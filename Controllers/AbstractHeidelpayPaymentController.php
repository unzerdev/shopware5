<?php

declare(strict_types=1);

namespace HeidelPayment\Controllers;

use Enlight_Components_Session_Namespace;
use Enlight_Controller_Router;
use HeidelPayment\Components\PaymentHandler\Structs\PaymentDataStruct;
use HeidelPayment\Installers\PaymentMethods;
use HeidelPayment\Services\Heidelpay\HeidelpayResourceHydratorInterface;
use HeidelPayment\Services\HeidelpayApiLoggerServiceInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Basket as HeidelpayBasket;
use heidelpayPHP\Resources\Customer as HeidelpayCustomer;
use heidelpayPHP\Resources\Metadata as HeidelpayMetadata;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use RuntimeException;
use Shopware_Components_Snippet_Manager;
use Shopware_Controllers_Frontend_Payment;

abstract class AbstractHeidelpayPaymentController extends Shopware_Controllers_Frontend_Payment
{
    /** @var BasePaymentType */
    protected $paymentType;

    /** @var PaymentDataStruct */
    protected $paymentDataStruct;

    /** @var Payment */
    protected $payment;

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
                $this->redirect($this->getHeidelpayErrorUrl('Error while fetching payment'));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postDispatch()
    {
        ini_set('precision', $this->phpPrecision);
        ini_set('serialize_precision', $this->phpSerializePrecision);

        if (!$this->isAsync) {
            $this->redirect($this->view->getAssign('redirectUrl'));
        }
    }

    public function pay(): void
    {
        $heidelBasket = $this->getHeidelpayBasket();

        try {
            $heidelCustomer = $this->getHeidelpayCustomer();
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating heidelpay customer', $apiException);
            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        $this->paymentDataStruct = new PaymentDataStruct($heidelBasket->getAmountTotalGross(), $heidelBasket->getCurrencyCode(), $this->getHeidelpayReturnUrl());

        $this->paymentDataStruct->fromArray([
            'customer' => $heidelCustomer,
            'metadata' => $this->getHeidelpayMetadata(),
            'basket'   => $this->getHeidelpayBasket(),
            'orderId'  => $heidelBasket->getOrderId(),
            'card3ds'  => true,
        ]);
    }

    protected function getHeidelpayCustomer(): HeidelpayCustomer
    {
        $user           = $this->getUser();
        $additionalData = $this->request->get('additional') ?: [];
        $customerId     = $additionalData['customerId'];

        if ($customerId) {
            return $this->heidelpayClient->fetchCustomerByExtCustomerId($customerId);
        }

        return $this->heidelpayClient->createOrUpdateCustomer($this->getCustomerByUser($user, $additionalData));
    }

    protected function getCustomerByUser(array $user, array $additionalData): HeidelpayCustomer
    {
        if ($additionalData && array_key_exists('birthday', $additionalData)) {
            $user['additional']['user']['birthday'] = $additionalData['birthday'];
        }

        if (!empty($user['billingaddress']['company']) && in_array($this->getPaymentShortName(), PaymentMethods::IS_B2B_ALLOWED)) {
            return $this->businessCustomerHydrator->hydrateOrFetch($user, $this->heidelpayClient);
        }

        return $this->customerHydrator->hydrateOrFetch($user, $this->heidelpayClient);
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
        ]);
    }

    protected function getHeidelpayErrorUrl(string $message = ''): string
    {
        return $this->router->assemble([
            'controller'       => 'checkout',
            'action'           => 'shippingPayment',
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
