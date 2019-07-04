<?php

use HeidelPayment\Installers\PaymentMethods;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;

class Shopware_Controllers_Frontend_Heidelpay extends Shopware_Controllers_Frontend_Payment
{
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

        $paymentObject = $heidelpayClient->fetchPayment($paymentId);

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
        $controller = '';

        switch ($paymentName) {
            case PaymentMethods::PAYMENT_NAME_SOFORT:
                $controller = 'HeidelpaySofort';
                break;
            case PaymentMethods::PAYMENT_NAME_FLEXIPAY:
                $controller = 'HeidelpayFlexipay';
                break;
            case PaymentMethods::PAYMENT_NAME_GIROPAY:
                $controller = 'HeidelpayGiropay';
                break;
        }

        return $controller;
    }
}
