<?php

declare(strict_types=1);

use HeidelPayment\Components\PaymentStatusMapper\AbstractStatusMapper;
use HeidelPayment\Components\PaymentStatusMapper\Exception\NoStatusMapperFoundException;
use HeidelPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use HeidelPayment\Components\WebhookHandler\Handler\WebhookHandlerInterface;
use HeidelPayment\Components\WebhookHandler\Struct\WebhookStruct;
use HeidelPayment\Components\WebhookHandler\WebhookSecurityException;
use HeidelPayment\Installers\Attributes;
use HeidelPayment\Services\HeidelpayApiLogger\HeidelpayApiLoggerServiceInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use Psr\Log\LogLevel;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Frontend_Heidelpay extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    private const WHITELISTED_CSRF_ACTIONS = [
        'executeWebhook',
    ];

    public function completePaymentAction(): void
    {
        $paymentObject = $this->getPaymentObject();

        if (empty($paymentObject)) {
            $this->redirectToErrorPage(
                $this->getHeidelpayErrorFromSnippet('exception/statusMapper')
            );

            return;
        }

        $paymentStatusId = $this->getPaymentStatusId($paymentObject);

        if ($paymentStatusId === AbstractStatusMapper::INVALID_STATUS) {
            $this->redirectToErrorPage(
                $this->getHeidelpayErrorFromSnippet('paymentCancelled')
            );

            return;
        }

        $basketSignatureHeidelpay = $paymentObject->getMetadata()->getMetadata('basketSignature');
        $this->loadBasketFromSignature($basketSignatureHeidelpay);

        $currentOrderNumber = $this->saveOrder($paymentObject->getId(), $paymentObject->getId(), $paymentStatusId);
        $this->saveTransactionIdToOrder($paymentObject, $currentOrderNumber);

        // Done, redirect to the finish page
        $this->redirect([
            'module'     => 'frontend',
            'controller' => 'checkout',
            'action'     => 'finish',
            'sUniqueID'  => $paymentObject->getId(),
        ]);
    }

    public function executeWebhookAction(): void
    {
        $webhookStruct = new WebhookStruct($this->request->getRawBody());

        $this->getApiLogger()->log('WEBHOOK - Request: ' . (string) $this->request->getRawBody());

        $webhookHandlerFactory  = $this->container->get('heidel_payment.factory.webhook');
        $heidelpayClientService = $this->container->get('heidel_payment.services.api_client');
        $handlers               = $webhookHandlerFactory->getWebhookHandlers($webhookStruct->getEvent());

        /** @var WebhookHandlerInterface $webhookHandler */
        foreach ($handlers as $webhookHandler) {
            if ($webhookStruct->getPublicKey() !== $heidelpayClientService->getPublicKey()) {
                throw new WebhookSecurityException();
            }

            $webhookHandler->execute($webhookStruct);
        }

        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHttpResponseCode(200);
    }

    public function getCustomerDataAction(): void
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
            'customer' => $heidelpayCustomer->expose(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getWhitelistedCSRFActions(): array
    {
        return self::WHITELISTED_CSRF_ACTIONS;
    }

    protected function getApiLogger(): HeidelpayApiLoggerServiceInterface
    {
        return $this->container->get('heidel_payment.services.api_logger');
    }

    private function getPaymentObject(): ?Payment
    {
        try {
            $session   = $this->container->get('session');
            $paymentId = (string) $session->offsetGet('heidelPaymentId');

            if (!$paymentId) {
                $this->getApiLogger()->getPluginLogger()->error(sprintf('There is no payment-id [%s]', $paymentId));

                $this->redirectToErrorPage(
                    $this->getHeidelpayErrorFromSnippet('exception/statusMapper')
                );

                return null;
            }

            $heidelpayClient = $this->container->get('heidel_payment.services.api_client')->getHeidelpayClient();

            return $heidelpayClient->fetchPayment($paymentId);
        } catch (HeidelpayApiException | RuntimeException $exception) {
            $this->getApiLogger()->logException(sprintf('Error while receiving payment details on finish page for payment-id [%s]', $paymentId), $exception);
        }

        return null;
    }

    private function getPaymentStatusId(Payment $paymentObject): int
    {
        $paymentStatusId = AbstractStatusMapper::INVALID_STATUS;

        try {
            $paymentStatusMapper = $this->container->get('heidel_payment.factory.status_mapper')
                ->getStatusMapper($paymentObject->getPaymentType());

            $paymentStatusId = $paymentStatusMapper->getTargetPaymentStatus($paymentObject);
        } catch (NoStatusMapperFoundException $ex) {
            $this->getApiLogger()->getPluginLogger()->error($ex->getMessage(), $ex->getTrace());

            $this->redirectToErrorPage($this->getHeidelpayErrorFromSnippet($ex->getCustomerMessage()));
        } catch (StatusMapperException $ex) {
            $this->getApiLogger()->log($ex->getMessage(), $ex->getTrace(), LogLevel::WARNING);

            $this->redirectToErrorPage($this->getHeidelpayErrorFromSnippet($ex->getCustomerMessage()));
        }

        return $paymentStatusId;
    }

    private function saveTransactionIdToOrder(Payment $paymentObject, string $orderNumber): void
    {
        $orderId = $this->getModelManager()->getDBALQueryBuilder()
            ->select('id')
            ->from('s_order')
            ->where('ordernumber = :orderNumber')
            ->setParameter('orderNumber', (string) $orderNumber)
            ->execute()->fetchColumn();

        if ($orderId) {
            $this->container->get('shopware_attribute.data_persister')
                ->persist([Attributes::HEIDEL_ATTRIBUTE_TRANSACTION_ID => $paymentObject->getOrderId()], 's_order_attributes', $orderId);
        }
    }

    private function redirectToErrorPage(string $message): void
    {
        $this->redirect([
            'controller'       => 'checkout',
            'action'           => 'shippingPayment',
            'heidelpayMessage' => urlencode($message),
        ]);
    }

    private function getHeidelpayErrorFromSnippet(string $snippetName, string $namespace = 'frontend/heidelpay/checkout/errors'): string
    {
        /** @var Shopware_Components_Snippet_Manager $snippetManager */
        $snippetManager = $this->container->get('snippets');

        return $snippetManager->getNamespace($namespace)->get($snippetName);
    }
}
