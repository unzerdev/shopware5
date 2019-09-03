<?php

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\InvoiceGuaranteed as InvoiceGuaranteedPaymentType;

class Shopware_Controllers_Widgets_HeidelpayInvoiceGuaranteed extends AbstractHeidelpayPaymentController
{
    /** @var InvoiceGuaranteedPaymentType */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        $additionalRequestData = $this->request->get('additional');

        if (!isset($additionalRequestData['birthday'])) {
            $this->view->assign([
                'success'     => false,
                'redirectUrl' => $this->getHeidelpayErrorUrl(),
            ]);

            return;
        }

        $user           = $this->getUser();
        $heidelCustomer = null;

        if (!empty($user['billingaddress']['company'])) {
            $heidelCustomer = $this->getHeidelpayB2bCustomer();
        } else {
            $heidelCustomer = $this->getHeidelpayB2cCustomer();
        }

        $heidelBasket   = $this->getHeidelpayBasket();
        $heidelMetadata = $this->getHeidelpayMetadata();
        $returnUrl      = $this->getHeidelpayReturnUrl();

        try {
            $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($heidelCustomer);
            $heidelCustomer->setBirthDate($additionalRequestData['birthday']);

            $result = $this->paymentType->charge(
                $heidelBasket->getAmountTotalGross(),
                $heidelBasket->getCurrencyCode(),
                $returnUrl,
                $heidelCustomer,
                $heidelBasket->getOrderId(),
                $heidelMetadata,
                $heidelBasket
            );

            $this->getApiLogger()->logResponse('Created invoice guaranteed payment', $result);
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating invoice guaranteed payment', $apiException);
            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $this->view->assign('redirectUrl', $result->getPayment()->getRedirectUrl() ?: $returnUrl);
        }
    }
}
