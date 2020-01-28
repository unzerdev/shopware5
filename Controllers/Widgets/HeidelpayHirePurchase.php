<?php

declare(strict_types=1);

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\HirePurchaseDirectDebit;

class Shopware_Controllers_Widgets_HeidelpayHirePurchase extends AbstractHeidelpayPaymentController
{
    /** @var HirePurchaseDirectDebit */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        if (!$this->paymentType) {
            return;
        }

        $birthDate      = $additionalRequestData['birthday'];
        $heidelBasket   = $this->getHeidelpayBasket();
        $heidelCustomer = $this->getHeidelpayB2cCustomer();
        $heidelMetadata = $this->getHeidelpayMetadata();
        $returnUrl      = $this->getHeidelpayReturnUrl();

        try {
            $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($heidelCustomer);
            $heidelCustomer->setBirthDate($birthDate);

            $authorization = $this->paymentType->authorize(
                $heidelBasket->getAmountTotalGross(),
                $heidelBasket->getCurrencyCode(),
                $returnUrl,
                $heidelCustomer,
                $heidelBasket->getOrderId(),
                $heidelMetadata,
                $heidelBasket
            );

            if ($authorization->getPayment()) {
                $result = $authorization->getPayment()->charge();
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating Flexipay payment', $apiException);
            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $this->view->assign('redirectUrl', $result->getPayment()->getRedirectUrl() ?: $returnUrl);
        }
    }
}
