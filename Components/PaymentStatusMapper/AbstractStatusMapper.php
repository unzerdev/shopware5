<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper;

use Shopware\Models\Order\Status;
use Shopware_Components_Snippet_Manager;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Shipment;

abstract class AbstractStatusMapper
{
    public const INVALID_STATUS = -42;

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

    protected function checkForRefund(Payment $paymentObject, int $currentStatus = self::INVALID_STATUS): int
    {
        $totalAmount     = $this->getAmountByFloat($paymentObject->getAmount()->getTotal());
        $cancelledAmount = $this->getAmountByFloat($paymentObject->getAmount()->getCanceled());
        $remainingAmount = $this->getAmountByFloat($paymentObject->getAmount()->getRemaining());

        if ($cancelledAmount === $totalAmount && $remainingAmount === 0) {
            return Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
        }

        return $currentStatus;
    }

    protected function checkForShipment(Payment $paymentObject, int $currentStatus = self::INVALID_STATUS): int
    {
        $shippedAmount   = 0;
        $totalAmount     = $this->getAmountByFloat($paymentObject->getAmount()->getTotal());
        $cancelledAmount = $this->getAmountByFloat($paymentObject->getAmount()->getCanceled());

        /** @var Shipment $shipment */
        foreach ($paymentObject->getShipments() as $shipment) {
            if (!empty($shipment->getAmount())) {
                $shippedAmount += $this->getAmountByFloat($shipment->getAmount());
            }
        }

        if ($shippedAmount === ($totalAmount - $cancelledAmount)) {
            return Status::PAYMENT_STATE_COMPLETELY_PAID;
        }

        return $currentStatus;
    }

    protected function getMessageFromSnippet(string $snippetName = 'paymentCancelled', string $snippetNamespace = 'frontend/unzerPayment/checkout/errors'): string
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

        if ($transaction instanceof Charge) {
            return $transaction->getMessage()->getCustomer();
        }

        return $this->getMessageFromSnippet();
    }

    protected function getAmountByFloat(float $amount): int
    {
        $defaultAmount = $amount;
        $amount        = (string) $amount;

        if (strrchr($amount, '.') !== false) {
            return (int) ($amount * (10 ** strlen(substr(strrchr($amount, '.'), 1))));
        }

        return (int) $defaultAmount;
    }
}
