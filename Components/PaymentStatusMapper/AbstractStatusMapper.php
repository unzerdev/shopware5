<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper;

use HeidelPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use Shopware\Models\Order\Status;
use Shopware_Components_Snippet_Manager;

abstract class AbstractStatusMapper
{
    /** @var Shopware_Components_Snippet_Manager */
    protected $snippetManager;

    /** @var ConfigReaderServiceInterface */
    protected $configReader;

    public function __construct(
        Shopware_Components_Snippet_Manager $snippetManager,
        ConfigReaderServiceInterface $configReader
    ) {
        $this->snippetManager = $snippetManager;
        $this->configReader   = $configReader;
    }

    protected function mapPaymentStatus(Payment $payment): int
    {
        $status = Status::PAYMENT_STATE_REVIEW_NECESSARY;

        if ($payment->isCanceled()) {
            $status = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
        } elseif ($payment->isPending()) {
            $status = Status::PAYMENT_STATE_RESERVED;
        } elseif ($payment->isChargeBack()) {
            $status = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
        } elseif ($payment->isCompleted()) {
            $status = Status::PAYMENT_STATE_COMPLETELY_PAID;
        }

        return $status;
    }

    protected function getMessageFromSnippet(string $snippetName = 'paymentCancelled', string $snippetNamespace = 'frontend/heidelpay/checkout/errors'): string
    {
        return $this->snippetManager->getNamespace($snippetNamespace)->get($snippetName);
    }

    protected function getMessageFromPaymentTransaction(Payment $payment): string
    {
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
