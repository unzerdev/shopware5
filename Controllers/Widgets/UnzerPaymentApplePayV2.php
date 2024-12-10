<?php

declare(strict_types=1);

use Shopware\Models\Shop\Shop;
use UnzerPayment\Components\BookingMode;
use UnzerPayment\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use UnzerPayment\Subscribers\Frontend\Checkout;
use UnzerSDK\Exceptions\UnzerApiException;

class Shopware_Controllers_Widgets_UnzerPaymentApplePayV2 extends AbstractUnzerPaymentController
{
    use CanAuthorize;
    use CanCharge;

    protected bool $isAsync = true;
    private ConfigReaderServiceInterface $configReader;
    private Shop $shop;

    public function preDispatch(): void
    {
        parent::preDispatch();

        $this->configReader = $this->container->get('unzer_payment.services.config_reader');
        $this->shop = $this->container->get('shop');
    }

    public function createPaymentAction(): void
    {
        parent::pay();

        $resourceId = $this->session->get(Checkout::UNZER_RESOURCE_ID);

        if (empty($resourceId)) {
            throw new RuntimeException('Cannot complete payment without resource id.');
        }

        $this->paymentType = $this->unzerPaymentClient->fetchPaymentType($resourceId);
        $this->handleNormalPayment();
    }


    private function handleNormalPayment(): void
    {
        $bookingMode = $this->configReader->get(
            'apple_pay_bookingmode',
            $this->shop->getId()
        );

        $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');

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
            $this->getApiLogger()->getPluginLogger()->error('Error while fetching payment', ['message' => $runtimeException->getMessage(), 'trace' => $runtimeException->getTrace()]);
        } finally {
            $this->session->set(Checkout::UNZER_RESOURCE_ID, null);

            $this->redirect($redirectUrl);
        }
    }
}
