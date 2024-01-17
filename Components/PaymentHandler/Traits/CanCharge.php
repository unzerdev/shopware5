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
 * @property Charge                                $paymentResult
 * @property Connection                            $connection
 * @property Enlight_Components_Session_Namespace $session
 */
trait CanCharge
{
    /**
     * @throws UnzerApiException|Exception
     */
    public function charge(string $returnUrl): string
    {
        if (!$this instanceof AbstractUnzerPaymentController) {
            throw new RuntimeException('Trait can only be used in a payment controller context which extends the AbstractUnzerPaymentController class');
        }

        if ($this->paymentType === null) {
            throw new RuntimeException('PaymentType can not be null');
        }

        if (!method_exists($this->paymentType, 'charge')) {
            throw new RuntimeException('This payment type does not support direct charge!');
        }

        $this->paymentResult = $this->paymentType->charge(
            $this->paymentDataStruct->getAmount(),
            $this->paymentDataStruct->getCurrency(),
            $this->paymentDataStruct->getReturnUrl(),
            $this->paymentDataStruct->getCustomer(),
            $this->paymentDataStruct->getOrderId(),
            $this->paymentDataStruct->getMetadata(),
            $this->paymentDataStruct->getBasket(),
            $this->paymentDataStruct->getCard3ds(),
            $this->paymentDataStruct->getInvoiceId(),
            $this->paymentDataStruct->getPaymentReference(),
            $this->paymentDataStruct->getRecurrenceType()
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
