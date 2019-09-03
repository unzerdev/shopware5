<?php

use HeidelPayment\Installers\PaymentMethods;
use HeidelPayment\Services\Heidelpay\Webhooks\Handlers\WebhookHandlerInterface;
use HeidelPayment\Services\Heidelpay\Webhooks\Struct\WebhookStruct;
use HeidelPayment\Services\Heidelpay\Webhooks\WebhookSecurityException;
use HeidelPayment\Services\HeidelpayApiLoggerServiceInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Frontend_Heidelpay extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    private const WHITELISTED_CSRF_ACTIONS = [
        'executeWebhook',
    ];

    /**
     * Proxy action for redirect payments.
     * Forwards to the correct widget payment controller.
     */
    public function proxyAction(): void
    {
        $paymentMethodName = $this->getPaymentShortName();
        $controller        = $this->getProxyControllerName($paymentMethodName);

        if (empty($controller)) {
            $this->redirect([
                'controller' => 'checkout',
                'action'     => 'confirm',
            ]);
        }

        $this->forward('createPayment', $controller, 'widgets');
    }

    public function completePaymentAction(): void
    {
        $session   = $this->container->get('session');
        $paymentId = $session->offsetGet('heidelPaymentId');

        if (!$paymentId) {
            $this->redirect([
                'controller' => 'checkout',
                'action'     => 'confirm',
            ]);

            return;
        }

        $heidelpayClient     = $this->container->get('heidel_payment.services.api_client')->getHeidelpayClient();
        $paymentStateFactory = $this->container->get('heidel_payment.services.payment_status_factory');

        try {
            $paymentObject = $heidelpayClient->fetchPayment($paymentId);

            $this->getApiLogger()->logResponse(sprintf('Received payment details on finish page for payment-id [%s]', $paymentId), $paymentObject);
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException(sprintf('Error while receiving payment details on finish page for payment-id [%s]', $paymentId), $apiException);

            $this->redirect([
                'controller' => 'checkout',
                'action'     => 'confirm',
            ]);

            return;
        }

        //e.g. 3ds failed
        if ($paymentObject->isCanceled()) {
            $this->redirectToErrorPage($this->getMessageFromPaymentTransaction($paymentObject));

            return;
        }

        $basketSignatureHeidelpay = $paymentObject->getMetadata()->getMetadata('basketSignature');
        $this->loadBasketFromSignature($basketSignatureHeidelpay);

        $this->saveOrder($paymentObject->getOrderId(), $paymentObject->getId(), $paymentStateFactory->getPaymentStatusId($paymentObject));

        // Done, redirect to the finish page
        $this->redirect([
            'module'     => 'frontend',
            'controller' => 'checkout',
            'action'     => 'finish',
        ]);
    }

    public function executeWebhookAction(): void
    {
        $webhookStruct = new WebhookStruct($this->request->getRawBody());

        $webhookHandlerFactory  = $this->container->get('heidel_payment.webhooks.factory');
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

    private function redirectToErrorPage(string $message): void
    {
        $this->redirect([
            'controller'       => 'checkout',
            'action'           => 'shippingPayment',
            'heidelpayMessage' => $message,
        ]);
    }

    private function getMessageFromPaymentTransaction(Payment $payment): string
    {
        // Check the result message of the transaction to find out what went wrong.
        $transaction = $payment->getAuthorization();

        if ($transaction instanceof Authorization) {
            return $transaction->getMessage()->getCustomer();
        }

        $transaction = $payment->getChargeByIndex(0);

        return $transaction->getMessage()->getCustomer();
    }

    private function getProxyControllerName(string $paymentName): string
    {
        switch ($paymentName) {
            case PaymentMethods::PAYMENT_NAME_SOFORT:
                return 'HeidelpaySofort';
            case PaymentMethods::PAYMENT_NAME_FLEXIPAY:
                return 'HeidelpayFlexipay';
            case PaymentMethods::PAYMENT_NAME_PAYPAL:
                return 'HeidelpayPaypal';
            case PaymentMethods::PAYMENT_NAME_GIROPAY:
                return 'HeidelpayGiropay';
            case PaymentMethods::PAYMENT_NAME_INVOICE:
                return 'HeidelpayInvoice';
            case PaymentMethods::PAYMENT_NAME_INVOICE_GUARANTEED:
                return 'HeidelpayInvoiceGuaranteed';
            case PaymentMethods::PAYMENT_NAME_INVOICE_FACTORING:
                return 'HeidelpayInvoiceFactoring';
            case PaymentMethods::PAYMENT_NAME_PRE_PAYMENT:
                return 'HeidelpayPrepayment';
            case PaymentMethods::PAYMENT_NAME_PREZLEWY:
                return 'HeidelpayPrezlewy';
            default:
                return '';
        }
    }
}
