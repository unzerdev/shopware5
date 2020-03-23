<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
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
            $status = $this->mapRefundStatus($paymentObject, Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED);

            if ($status !== 0) {
                return $status;
            }
        } elseif ($paymentObject->isPending()) {
            $status = Status::PAYMENT_STATE_RESERVED;
        } elseif ($paymentObject->isChargeBack()) {
            $status = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
        } elseif ($paymentObject->isCompleted()) {
            $status = Status::PAYMENT_STATE_COMPLETELY_PAID;
        }

        return $status;
    }

    protected function mapRefundStatus(Payment $paymentObject, int $currentStatus = 0): int
    {
        if (!empty($paymentObject->getCharges())) {
            if ($this->isRefunded($paymentObject->getCharges())) {
                $chargedAmount = $paymentObject->getAmount()->getCharged();

                if (!strrchr((string) $chargedAmount, '.') || ($chargedAmount * (10 ** strlen(substr(strrchr((string) $chargedAmount, '.'), 1))) === 0)) {
                    return Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
                }

                return Status::PAYMENT_STATE_RE_CREDITING;
            }
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

    /**
     * @param Charge[] $charges
     */
    protected function isRefunded(array $charges): bool
    {
        foreach ($charges as $charge) {
            if (!empty($charge->getCancellations())) {
                return true;
            }
        }

        return false;
    }
}
