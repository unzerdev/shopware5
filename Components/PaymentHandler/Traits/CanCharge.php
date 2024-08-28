<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentHandler\Traits;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Enlight_Components_Session_Namespace;
use RuntimeException;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\Charge;

/**
 * @property Charge                               $paymentResult
 * @property Connection                           $connection
 * @property Enlight_Components_Session_Namespace $session
 */
trait CanCharge
{
    /**
     * @throws Exception|UnzerApiException
     */
    public function charge(string $returnUrl): string
    {
        if (!$this instanceof AbstractUnzerPaymentController) {
            throw new RuntimeException('Trait can only be used in a payment controller context which extends the AbstractUnzerPaymentController class');
        }

        if ($this->paymentType === null) {
            throw new RuntimeException('PaymentType can not be null');
        }

        $charge = (new Charge($this->paymentDataStruct->getAmount(), $this->paymentDataStruct->getCurrency(), $returnUrl))
            ->setOrderId($this->paymentDataStruct->getOrderId())
            ->setInvoiceId($this->paymentDataStruct->getInvoiceId())
            ->setPaymentReference($this->paymentDataStruct->getPaymentReference());
        if ($this->paymentDataStruct->getCard3ds()) {
            $charge->setCard3ds($this->paymentDataStruct->getCard3ds());
        }

        if ($this->paymentDataStruct->getRecurrenceType() !== null) {
            $charge->setRecurrenceType($this->paymentDataStruct->getRecurrenceType());
        }

        $this->paymentResult = $this->unzerPaymentClient->performCharge(
                $charge,
                $this->paymentType,
                $this->paymentDataStruct->getCustomer(),
                $this->paymentDataStruct->getMetadata(),
                $this->paymentDataStruct->getBasket()
            );

        $this->payment = $this->paymentResult->getPayment();

        $this->session->offsetSet('unzerPaymentId', $this->payment->getId());

        $this->connection->executeStatement(
            'UPDATE s_order SET temporaryID = ? WHERE temporaryID = ? AND ordernumber = ?',
            [$this->payment->getId(), $this->session->get('sessionId'), '0']
        );

        if ($this->payment !== null && !empty($this->paymentResult->getRedirectUrl())) {
            return $this->paymentResult->getRedirectUrl();
        }

        return $returnUrl;
    }
}
