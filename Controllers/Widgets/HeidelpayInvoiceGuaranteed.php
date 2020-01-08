<?php

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\InvoiceGuaranteed as InvoiceGuaranteedPaymentType;

class Shopware_Controllers_Widgets_HeidelpayInvoiceGuaranteed extends AbstractHeidelpayPaymentController
{
    /** @var InvoiceGuaranteedPaymentType */
    protected $paymentType;

    /** @var bool */
    protected $isAsync = true;

    public function createPaymentAction(): void
    {
        if (!$this->paymentType) {
            $this->handleCommunicationError();

            return;
        }

        $additionalRequestData = $this->request->get('additional');
        $birthday              = $additionalRequestData['birthday'] ?: null;
        $b2bCustomerId         = $additionalRequestData['customerId'];
        $heidelBasket          = $this->getHeidelpayBasket();
        $heidelMetadata        = $this->getHeidelpayMetadata();
        $returnUrl             = $this->getHeidelpayReturnUrl();

        if ($b2bCustomerId) {
            $heidelCustomer = $this->heidelpayClient->fetchCustomer($b2bCustomerId);
        } else {
            $heidelCustomer = $this->getHeidelpayB2cCustomer();
            $heidelCustomer->setBirthDate((string) $birthday);
            $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($heidelCustomer);
        }

        try {
            $result = $this->paymentType->charge(
                $heidelBasket->getAmountTotalGross(),
                $heidelBasket->getCurrencyCode(),
                $returnUrl,
                $heidelCustomer,
                $heidelBasket->getOrderId(),
                $heidelMetadata,
                $heidelBasket
            );
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating invoice guaranteed payment', $apiException);
            $this->view->assign('redirectUrl', $this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $this->view->assign('redirectUrl', $result->getPayment()->getRedirectUrl() ?: $returnUrl);
        }
    }
}
