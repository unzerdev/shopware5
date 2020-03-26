<?php

declare(strict_types=1);

namespace HeidelPayment\Controllers;

use Enlight_Components_Session_Namespace;
use Enlight_Controller_Router;
use HeidelPayment\Components\Hydrator\ResourceHydrator\ResourceHydratorInterface;
use HeidelPayment\Components\PaymentHandler\Structs\PaymentDataStruct;
use HeidelPayment\Installers\PaymentMethods;
use HeidelPayment\Services\HeidelpayApiLogger\HeidelpayApiLoggerServiceInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Basket as HeidelpayBasket;
use heidelpayPHP\Resources\Customer as HeidelpayCustomer;
use heidelpayPHP\Resources\Metadata as HeidelpayMetadata;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\Recurring;
use PDO;
use RuntimeException;
use Shopware\Bundle\AttributeBundle\Service\DataPersister;
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

    /** @var Recurring */
    protected $recurring;

    /** @var Heidelpay */
    protected $heidelpayClient;

    /** @var Enlight_Components_Session_Namespace */
    protected $session;

    /** @var DataPersister */
    protected $dataPersister;

    /** @var bool */
    protected $isAsync;

    /** @var bool */
    protected $isChargeRecurring = false;

    /** @var ResourceHydratorInterface */
    private $basketHydrator;

    /** @var ResourceHydratorInterface */
    private $customerHydrator;

    /** @var ResourceHydratorInterface */
    private $businessCustomerHydrator;

    /** @var ResourceHydratorInterface */
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

        $this->customerHydrator         = $this->container->get('heidel_payment.resource_hydrator.private_customer');
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
                $this->getApiLogger()->logException(sprintf('Error while fetching payment type by id [%s]', $paymentTypeId), $apiException);
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

        if (!$this->isAsync && !$this->isChargeRecurring) {
            $this->redirect($this->view->getAssign('redirectUrl'));
        }
    }

    public function pay(): void
    {
        $heidelBasket   = $this->getHeidelpayBasket();
        $heidelCustomer = $this->getHeidelpayCustomer();

        $this->paymentDataStruct = new PaymentDataStruct($heidelBasket->getAmountTotalGross(), $heidelBasket->getCurrencyCode(), $this->getHeidelpayReturnUrl());

        $this->paymentDataStruct->fromArray([
            'customer'    => $heidelCustomer,
            'metadata'    => $this->getHeidelpayMetadata(),
            'basket'      => $heidelBasket,
            'orderId'     => $heidelBasket->getOrderId(),
            'card3ds'     => true,
            'isRecurring' => $heidelBasket->getSpecialParams()['isAbo'] ?: false,
        ]);
    }

    public function recurring(): void
    {
        $this->isChargeRecurring = true;
        $this->dataPersister     = $this->container->get('shopware_attribute.data_persister');
        $this->request->setParam('typeId', 'notNull');

        $recurringData = $this->container->get('heidel_payment.array_hydrator.recurring_data')
            ->hydrateRecurringData((float) $this->getBasket()['AmountWithTaxNumeric'], (int) $this->request->getParam('orderId'));

        if (!$recurringData['order'] || !$recurringData['aboId'] || !$recurringData['basketAmount'] || !$recurringData['transactionId'] || $recurringData['basketAmount'] === 0.0) {
            $this->getApiLogger()->getPluginLogger()->error('Recurring activation failed since at least one of the following values is empty:' . json_encode($recurringData));
            $this->view->assign('success', false);

            return;
        }

        $payment = $this->getPaymentByTransactionId($recurringData['transactionId']);

        if (!$payment) {
            $this->getApiLogger()->getPluginLogger()->error('The payment could not be found');
            $this->view->assign('success', false);

            return;
        }

        $this->paymentType = $this->getPaymentTypeByPaymentTypeId($payment->getPaymentType()->getId());

        if (!$this->paymentType) {
            $this->getApiLogger()->getPluginLogger()->error('The payment type could not be created');
            $this->view->assign('success', false);

            return;
        }

        $heidelBasket = $this->handleRecurringBasket($recurringData['order']);

        $this->paymentDataStruct = new PaymentDataStruct($heidelBasket->getAmountTotalGross(), $recurringData['order']['currency'], $this->getChargeRecurringUrl());
        $this->paymentDataStruct->fromArray([
            'basket'           => $heidelBasket,
            'customer'         => $payment->getCustomer(),
            'orderId'          => $payment->getOrderId(),
            'metaData'         => $payment->getMetadata(),
            'paymentReference' => $recurringData['transactionId'],
            'recurringData'    => [
                'swAboId' => (int) $recurringData['aboId'],
            ],
        ]);
    }

    protected function getHeidelpayCustomer(): ?HeidelpayCustomer
    {
        $user           = $this->getUser();
        $additionalData = $this->request->get('additional') ?: [];
        $customerId     = $additionalData['customerId'];

        try {
            if ($customerId) {
                return $this->heidelpayClient->fetchCustomerByExtCustomerId($customerId);
            }

            return $this->heidelpayClient->createOrUpdateCustomer($this->getCustomerByUser($user, $additionalData));
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException($apiException->getMessage(), $apiException);
            $this->view->assign('redirectUrl', $this->getHeidelpayErrorUrlFromSnippet('frontend/heidelpay/checkout/confirm', 'communicationError'));

            return null;
        }
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
            'module'     => 'frontend',
        ]);
    }

    protected function getChargeRecurringUrl(): string
    {
        return $this->router->assemble([
            'module'     => 'frontend',
            'controller' => 'HeidelpayProxy',
            'action'     => 'recurring',
        ]) ?: '';
    }

    protected function getInitialRecurringUrl(): string
    {
        return $this->router->assemble([
            'module'     => 'frontend',
            'controller' => 'HeidelpayProxy',
            'action'     => 'initialRecurring',
        ]) ?: '';
    }

    protected function getHeidelpayErrorUrl(string $message = ''): string
    {
        return $this->router->assemble([
            'controller'       => 'checkout',
            'action'           => 'shippingPayment',
            'module'           => 'frontend',
            'heidelpayMessage' => urlencode($message),
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

    protected function getOrderDataById(int $orderId): array
    {
        return $this->getModelManager()->getDBALQueryBuilder()
            ->select('*')
            ->from('s_order')
            ->where('id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getAboByOrderId(int $orderId): array
    {
        return $this->getModelManager()->getDBALQueryBuilder()
            ->select('*')
            ->from('s_plugin_swag_abo_commerce_orders')
            ->where('last_order_id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getPaymentByTransactionId(string $transactionId): ?Payment
    {
        if (!$transactionId) {
            return null;
        }

        try {
            $payment = $this->heidelpayClient->fetchPaymentByOrderId($transactionId);
        } catch (HeidelpayApiException $heidelpayApiException) {
            $this->getApiLogger()->logException($heidelpayApiException->getMessage(), $heidelpayApiException);
        }

        return $payment ?: null;
    }

    protected function getPaymentTypeByPaymentTypeId(string $paymentTypeId): ?BasePaymentType
    {
        try {
            $paymentType = $this->heidelpayClient->fetchPaymentType($paymentTypeId);
            $paymentType->setParentResource($this->heidelpayClient);
        } catch (HeidelpayApiException $heidelpayApiException) {
            $this->getApiLogger()->logException($heidelpayApiException->getMessage(), $heidelpayApiException);
        }

        return $paymentType ?: null;
    }

    protected function handleRecurringBasket(array $order): HeidelpayBasket
    {
        $sOrderVariables                             = $this->session->offsetGet('sOrderVariables');
        $sOrderVariables['sBasket']['sCurrencyName'] = $order['currency'];

        if (empty($sOrderVariables['sBasket']['AmountWithTaxNumeric'])) {
            $sOrderVariables['sBasket']['AmountWithTaxNumeric'] = $sOrderVariables['sBasket']['AmountNumeric'];
        }

        if (empty($sOrderVariables['sBasket']['sAmountWithTax']) && !empty($sOrderVariables['sAmountWithTax'])) {
            $sOrderVariables['sBasket']['sAmountWithTax'] = $sOrderVariables['sAmountWithTax'];
        }

        $this->session->offsetSet('sOrderVariables', $sOrderVariables);

        $heidelBasket     = $this->getHeidelpayBasket();
        $amountTotalGross = (float) $sOrderVariables['sBasket']['AmountWithTaxNumeric'];
        $amountTotalNet   = (float) $sOrderVariables['sBasket']['sAmount'];
        $heidelBasket->setAmountTotalGross($amountTotalGross);
        $heidelBasket->setAmountTotalVat($amountTotalGross - $amountTotalNet);

        return $heidelBasket;
    }
}
