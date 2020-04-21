<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use Shopware\Models\Order\Status;
use Shopware_Components_Snippet_Manager;

abstract class AbstractStatusMapper
{
    /** @var Shopware_Components_Snippet_Manager */
    protected $snippetManager;

    public function __construct(Shopware_Components_Snippet_Manager $snippetManager)
    {
        $this->snippetManager = $snippetManager;
    }

    protected function mapPaymentStatus(Payment $paymentObject): int
    {
        $status = Status::PAYMENT_STATE_REVIEW_NECESSARY;

        if ($paymentObject->isCanceled()) {
            $status = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
        } elseif ($paymentObject->isPending()) {
            $status = Status::PAYMENT_STATE_RESERVED;
        } elseif ($paymentObject->isChargeBack()) {
            $status = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
        } elseif ($paymentObject->isPartlyPaid()) {
            $status = Status::PAYMENT_STATE_PARTIALLY_PAID;
        } elseif ($paymentObject->isCompleted()) {
            $status = Status::PAYMENT_STATE_COMPLETELY_PAID;
        }

        return $this->checkForRefund($paymentObject, $status);
    }

    protected function checkForRefund(Payment $paymentObject, int $currentStatus = Status::PAYMENT_STATE_REVIEW_NECESSARY): int
    {
        $totalAmount     = $this->getTotalAmount((string) $paymentObject->getAmount()->getTotal());
        $cancelledAmount = $this->getCancelledAmount((string) $paymentObject->getAmount()->getCanceled());
        $remainingAmount = $this->getRemainingAmount((string) $paymentObject->getAmount()->getRemaining());

        if ($cancelledAmount === $totalAmount && $remainingAmount === 0) {
            return Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
        }

        return $currentStatus;
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

    protected function getTotalAmount(string $totalAmount): int
    {
        if (strrchr($totalAmount, '.') !== false) {
            return (int) ($totalAmount * (10 ** strlen(substr(strrchr($totalAmount, '.'), 1))));
        }

        return (int) $totalAmount;
    }

    protected function getCancelledAmount(string $cancelledAmount): int
    {
        if (strrchr($cancelledAmount, '.') !== false) {
            return (int) ($cancelledAmount * (10 ** strlen(substr(strrchr($cancelledAmount, '.'), 1))));
        }

        return 0;
    }

    protected function getRemainingAmount(string $remainingAmount): int
    {
        if (strrchr($remainingAmount, '.') !== false) {
            return (int) ($remainingAmount * (10 ** strlen(substr(strrchr($remainingAmount, '.'), 1))));
        }

        return 0;
    }
}
