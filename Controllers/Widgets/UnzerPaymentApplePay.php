<?php

declare(strict_types=1);

use UnzerPayment\Components\BookingMode;
use UnzerPayment\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\Applepay;

class Shopware_Controllers_Widgets_UnzerPaymentApplePay extends AbstractUnzerPaymentController
{
    use CanAuthorize;
    use CanCharge;

    /** @var bool */
    protected $isAsync = true;

    /** @var bool */
    protected $isRedirectPayment = true;

    public function preDispatch(): void
    {
        parent::preDispatch();
    }

    public function createPaymentAction(): void
    {
        parent::pay();

        $this->paymentType = $this->unzerPaymentClient->createPaymentType(new Applepay());

        $this->handleNormalPayment();
    }

    private function handleNormalPayment(): void
    {
        $bookingMode = $this->container->get('unzer_payment.services.config_reader')->get('apple_pay_bookingmode');

        try {
            if ($bookingMode === BookingMode::CHARGE) {
                $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());
            } else {
                $redirectUrl = $this->authorize($this->paymentDataStruct->getReturnUrl());
            }
        } catch (UnzerApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating apple pay payment', $apiException);
            $redirectUrl = $this->getUnzerPaymentErrorUrl($apiException->getClientMessage());
        } catch (RuntimeException $runtimeException) {
            $this->getApiLogger()->getPluginLogger()->error('Error while fetching payment', $runtimeException->getTrace());
            $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
        } finally {
            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }
}
