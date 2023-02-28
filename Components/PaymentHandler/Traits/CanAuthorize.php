<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentHandler\Traits;

use Doctrine\DBAL\Connection;
use RuntimeException;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\EmbeddedResources\RiskData;
use UnzerSDK\Resources\TransactionTypes\Authorization;

/**
 * @property Authorization                         $paymentResult
 * @property Connection                            $connection
 * @property \Enlight_Components_Session_Namespace $session
 */
trait CanAuthorize
{
    /**
     * @throws UnzerApiException
     */
    public function authorize(string $returnUrl, ?RiskData $riskData = null): string
    {
        if ($this->unzerPaymentClient === null) {
            throw new RuntimeException('UnzerClient can not be null');
        }

        if (!method_exists($this->unzerPaymentClient, 'performAuthorization')) {
            throw new RuntimeException('The SDK Version is older then expected');
        }

        if (!$this instanceof AbstractUnzerPaymentController) {
            throw new RuntimeException('Trait can only be used in a payment controller context which extends the AbstractUnzerPaymentController class');
        }

        if ($this->paymentType === null) {
            throw new RuntimeException('PaymentType can not be null');
        }

        $authorization = new Authorization(
            $this->paymentDataStruct->getAmount(),
            $this->paymentDataStruct->getCurrency(),
            $this->paymentDataStruct->getReturnUrl()
        );
        $authorization->setOrderId($this->paymentDataStruct->getOrderId());
        $authorization->setCard3ds($this->paymentDataStruct->getCard3ds());

        if (null !== $this->paymentDataStruct->getRecurrenceType()) {
            $authorization->setRecurrenceType($this->paymentDataStruct->getRecurrenceType());
        }

        if (null !== $riskData) {
            $authorization->setRiskData($riskData);
        }

        $this->paymentResult = $this->unzerPaymentClient->performAuthorization(
            $authorization,
            $this->paymentType,
            $this->getUnzerPaymentCustomer(),
            $this->paymentDataStruct->getMetadata(),
            $this->paymentDataStruct->getBasket()
        );

        $this->payment = $this->paymentResult->getPayment();

        $this->session->offsetSet('unzerPaymentId', $this->payment->getId());

        $this->connection->executeUpdate(
            'UPDATE s_order SET temporaryID = ? WHERE temporaryID = ? AND ordernumber = ?',
            [$this->payment->getId(), $this->session->get('sessionId'), '0']
        );

        if ($this->payment !== null && !empty($this->paymentResult->getRedirectUrl())) {
            return $this->paymentResult->getRedirectUrl();
        }

        return $returnUrl;
    }
}
