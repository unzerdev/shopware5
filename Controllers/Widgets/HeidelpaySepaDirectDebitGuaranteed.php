<?php

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\SepaDirectDebitGuaranteed as SepaDirectDebitGuaranteedPaymentType;

class Shopware_Controllers_Widgets_HeidelpaySepaDirectDebitGuaranteed extends AbstractHeidelpayPaymentController
{
    /** @var SepaDirectDebitGuaranteedPaymentType */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        $mandateAccepted = (bool) $this->request->get('mandateAccepted');

        if (!$mandateAccepted) {
            $this->view->assign([
                'success'     => false,
                'redirectUrl' => $this->getHeidelpayErrorUrl(),
            ]);

            return;
        }

        $heidelBasket   = $this->getHeidelpayBasket();
        $heidelCustomer = $this->getHeidelpayCustomer();
        $heidelMetadata = $this->getHeidelpayMetadata();
        $returnUrl      = $this->getHeidelpayReturnUrl();

        try {
            $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($heidelCustomer);

            $result = $this->paymentType->charge(
                $heidelBasket->getAmountTotal(),
                $heidelBasket->getCurrencyCode(),
                $returnUrl,
                $heidelCustomer,
                $heidelBasket->getOrderId(),
                $heidelMetadata,
                $heidelBasket
            );

            $this->getApiLogger()->logResponse('Created SEPA direct debit guaranteed payment', $result);
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating SEPA direct debit guaranteed payment', $apiException);

            $this->view->assign('redirectUrl', $this->getHeidelpayErrorUrl($apiException->getMerchantMessage()));
        }

        $this->view->assign('success', isset($result));

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $this->view->assign('redirectUrl', $result->getPayment()->getRedirectUrl() ?: $returnUrl);
        }
    }
}
