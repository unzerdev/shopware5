<?php

declare(strict_types=1);

use Psr\Log\LogLevel;
use Shopware\Components\CSRFWhitelistAware;
use UnzerPayment\Components\Hydrator\ResourceHydrator\CustomerHydrator\BusinessCustomerHydrator;
use UnzerPayment\Components\PaymentStatusMapper\AbstractStatusMapper;
use UnzerPayment\Components\PaymentStatusMapper\Exception\NoStatusMapperFoundException;
use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerPayment\Components\WebhookHandler\Handler\WebhookHandlerInterface;
use UnzerPayment\Components\WebhookHandler\Struct\WebhookStruct;
use UnzerPayment\Components\WebhookHandler\WebhookSecurityException;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;

class Shopware_Controllers_Frontend_UnzerPayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    private const WHITELISTED_CSRF_ACTIONS = [
        'executeWebhook',
    ];

    public function completePaymentAction(): void
    {
        $paymentObject = $this->getPaymentObject();

        if (empty($paymentObject)) {
            $this->redirectToErrorPage(
                $this->getUnzerPaymentErrorFromSnippet('exception/statusMapper')
            );

            return;
        }

        $paymentStatusId = $this->getPaymentStatusId($paymentObject);

        if ($paymentStatusId === AbstractStatusMapper::INVALID_STATUS) {
            $this->redirectToErrorPage(
                $this->getUnzerPaymentErrorFromSnippet('paymentCancelled')
            );

            return;
        }

        $unzerPaymentBasketSignature = $paymentObject->getMetadata()->getMetadata('basketSignature');
        $this->loadBasketFromSignature($unzerPaymentBasketSignature);

        $this->saveOrder($paymentObject->getOrderId(), $paymentObject->getId(), $paymentStatusId);

        $this->container->get('dbal_connection')->delete(
            's_order',
            ['temporaryID' => $paymentObject->getId(), 'ordernumber' => '0']
        );

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

        $webhookHandlerFactory     = $this->container->get('unzer_payment.factory.webhook');
        $unzerPaymentClientService = $this->container->get('unzer_payment.services.api_client');
        $handlers                  = $webhookHandlerFactory->getWebhookHandlers($webhookStruct->getEvent());

        /** @var WebhookHandlerInterface $webhookHandler */
        foreach ($handlers as $webhookHandler) {
            if ($webhookStruct->getPublicKey() !== $unzerPaymentClientService->getPublicKey()) {
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

        $session  = $this->container->get('session');
        $userData = $session->offsetGet('sOrderVariables')['sUserData'];

        /** @var BusinessCustomerHydrator $customerHydrationService */
        $customerHydrationService = $this->container->get('unzer_payment.resource_hydrator.business_customer');

        $unzerPaymentCustomer = null;

        if (!empty($userData)) {
            $unzerPaymentCustomer = $customerHydrationService->hydrateOrFetch($userData)->expose();
        }

        $this->view->assign([
            'success'  => isset($unzerPaymentCustomer),
            'customer' => $unzerPaymentCustomer,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getWhitelistedCSRFActions(): array
    {
        return self::WHITELISTED_CSRF_ACTIONS;
    }

    protected function getApiLogger(): UnzerPaymentApiLoggerServiceInterface
    {
        return $this->container->get('unzer_payment.services.api_logger');
    }

    private function getPaymentObject(): ?Payment
    {
        try {
            $session   = $this->container->get('session');
            $paymentId = (string) $session->offsetGet('unzerPaymentId');

            if (empty($paymentId)) {
                $this->getApiLogger()->getPluginLogger()->error(sprintf('There is no payment-id [%s]', $paymentId));

                $this->redirectToErrorPage(
                    $this->getUnzerPaymentErrorFromSnippet('exception/statusMapper')
                );

                return null;
            }

            /** @var UnzerPaymentClientServiceInterface $unzerClientService */
            $unzerClientService = $this->container->get('unzer_payment.services.api_client');

            $unzerPaymentClient = $unzerClientService->getUnzerPaymentClient();

            return $unzerPaymentClient->fetchPayment($paymentId);
        } catch (UnzerApiException | RuntimeException $exception) {
            if (empty($paymentId)) {
                $paymentId = 'unknown';
            }

            $this->getApiLogger()->logException(sprintf('Error while receiving payment details on finish page for payment-id [%s]', $paymentId), $exception);
        }

        return null;
    }

    private function getPaymentStatusId(Payment $paymentObject): int
    {
        $paymentStatusId = AbstractStatusMapper::INVALID_STATUS;

        try {
            $paymentStatusMapper = $this->container->get('unzer_payment.factory.status_mapper')
                ->getStatusMapper($paymentObject->getPaymentType());

            $paymentStatusId = $paymentStatusMapper->getTargetPaymentStatus($paymentObject);
        } catch (NoStatusMapperFoundException $ex) {
            $this->getApiLogger()->getPluginLogger()->error($ex->getMessage(), $ex->getTrace());

            $this->redirectToErrorPage($this->getUnzerPaymentErrorFromSnippet($ex->getCustomerMessage()));
        } catch (StatusMapperException $ex) {
            $this->getApiLogger()->log($ex->getMessage(), $ex->getTrace(), LogLevel::WARNING);

            $this->redirectToErrorPage($this->getUnzerPaymentErrorFromSnippet($ex->getCustomerMessage()));
        }

        return $paymentStatusId;
    }

    private function redirectToErrorPage(string $message): void
    {
        $this->redirect([
            'controller'          => 'checkout',
            'action'              => 'shippingPayment',
            'unzerPaymentMessage' => urlencode($message),
        ]);
    }

    private function getUnzerPaymentErrorFromSnippet(string $snippetName, string $namespace = 'frontend/unzer_payment/checkout/errors'): string
    {
        /** @var Shopware_Components_Snippet_Manager $snippetManager */
        $snippetManager = $this->container->get('snippets');

        return $snippetManager->getNamespace($namespace)->get($snippetName);
    }
}
