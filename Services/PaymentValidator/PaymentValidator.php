<?php

declare(strict_types=1);

namespace HeidelPayment\Services\PaymentValidator;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use Shopware_Components_Snippet_Manager;
use Shopware_Controllers_Frontend_Heidelpay;

class PaymentValidator implements PaymentValidatorInterface
{
    /** @var Shopware_Components_Snippet_Manager */
    private $snippetManager;

    public function __construct(Shopware_Components_Snippet_Manager $snippetManager)
    {
        $this->snippetManager = $snippetManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validatePaymentObject(Payment $paymentObject, string $paymentMethodShortName): string
    {
        //Treat redirect payments with state "pending" as "cancelled". Does not apply to anything else but redirect payments.
        if ($paymentObject->isPending()
            && array_key_exists($paymentMethodName, Shopware_Controllers_Frontend_Heidelpay::PAYMENT_CONTROLLER_MAPPING)
            && !in_array($paymentMethodName, PaymentValidatorInterface::PAYMENT_STATUS_PENDING_ALLOWED)
        ) {
            return $this->snippetManager->getNamespace('frontend/heidelpay/checkout/errors')->get('paymentCancelled');
        }

        // Fix for MGW behavior if a customer aborts the OT-payment and produces pending payment
        switch (true) {
            case $paymentObject->getPaymentType() instanceof PaymentTypes\EPS:
            case $paymentObject->getPaymentType() instanceof PaymentTypes\Giropay:
            case $paymentObject->getPaymentType() instanceof PaymentTypes\Ideal:
            case $paymentObject->getPaymentType() instanceof PaymentTypes\Paypal:
            case $paymentObject->getPaymentType() instanceof PaymentTypes\PIS:
            case $paymentObject->getPaymentType() instanceof PaymentTypes\Przelewy24:
            case $paymentObject->getPaymentType() instanceof PaymentTypes\Sofort:
                if ($paymentObject->isPending() || $paymentObject->isCanceled() || $paymentObject->isPaymentReview()) {
                    return $this->getMessageFromPaymentTransaction($paymentObject);
                }
        }

        //e.g. 3ds failed
        if ($paymentObject->isCanceled()) {
            return $this->getMessageFromPaymentTransaction($paymentObject);
        }

        return '';
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
}
