<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentValidator;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use Shopware_Components_Snippet_Manager;

abstract class AbstractPaymentValidator
{
    /** @var Shopware_Components_Snippet_Manager */
    protected $snippetManager;

    public function __construct(Shopware_Components_Snippet_Manager $snippetManager)
    {
        $this->snippetManager = $snippetManager;
    }

    protected function getPaymentShortName(): string
    {
        return self::PAYMENT_METHOD_SHORT_NAME;
    }

    protected function getMessageFromSnippet(string $snippetName = 'paymentCancelled', string $snippetNamespace = 'frontend/heidelpay/checkout/errors'): string
    {
        return $this->snippetManager->getNamespace($snippetNamespace)->get($snippetName);
    }

    protected function getMessageFromPaymentTransaction(Payment $payment): string
    {
        // Check the result message of the transaction to find out what went wrong.
        $transaction = $payment->getAuthorization();

        if ($transaction instanceof Authorization) {
            return $transaction->getMessage()->getCustomer();
        }

        $transaction = $payment->getChargeByIndex(0);

        if (!$transaction) {
            return $this->getMessageFromSnippet();
        }

        return $transaction->getMessage()->getCustomer();
    }
}
