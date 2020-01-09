<?php

declare(strict_types=1);

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use HeidelPayment\Services\Heidelpay\Webhooks\Handlers\WebhookHandlerInterface;
use HeidelPayment\Services\Heidelpay\Webhooks\Struct\WebhookStruct;
use HeidelPayment\Services\Heidelpay\Webhooks\WebhookSecurityException;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Frontend_HeidelpayRecurringPaypal extends AbstractHeidelpayPaymentController implements CSRFWhitelistAware
{
    private const WHITELISTED_CSRF_ACTIONS = [
        'executeWebhook',
    ];
    /** @var Paypal */
    protected $paymentType;

    public function getWhitelistedCSRFActions()
    {
        return self::WHITELISTED_CSRF_ACTIONS;
    }

    public function executeWebhookAction()
    {
        $webhookStruct = new WebhookStruct($this->request->getRawBody());

        $webhookHandlerFactory  = $this->container->get('heidel_payment.webhooks.factory');
        $heidelpayClientService = $this->container->get('heidel_payment.services.api_client');
        $handlers               = $webhookHandlerFactory->getWebhookHandlers($webhookStruct->getEvent());

        /** @var WebhookHandlerInterface $webhookHandler */
        foreach ($handlers as $webhookHandler) {
            if ($webhookStruct->getPublicKey() !== $heidelpayClientService->getPublicKey()) {
                throw new WebhookSecurityException();
            }

            $webhookHandler->execute($webhookStruct);
        }

        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHttpResponseCode(200);
    }

    public function checkAction()
    {
        $session       = $this->container->get('session');
        $paymentTypeId = $session->offsetGet('PaymentTypeId');

        try {
            $heidelpayClient   = $this->container->get('heidel_payment.services.api_client')->getHeidelpayClient();
            $this->paymentType = $heidelpayClient->fetchPaymentType($paymentTypeId);

            if ($this->paymentType instanceof Paypal && $this->paymentType->isRecurring()) {
                $chargeResult = $this->chargeRecurring();

                if (!$chargeResult) {
//                    TODO: enhance message
//                    $this->redirect($thwis->getHeidelpayErrorUrl('not chargeable'));
                }

                $this->session->offsetSet('heidelPaymentId', $chargeResult->getOrderId());
                $this->redirect($chargeResult->getReturnUrl());
            }
        } catch (HeidelpayApiException $e) {
            $merchantMessage = $e->getMerchantMessage();
            $clientMessage   = $e->getClientMessage();
            $this->getApiLogger()->logException('Error while creating PayPal recurring payment', $apiException);

            $this->redirect($thwis->getHeidelpayErrorUrl($apiException->getClientMessage()));
        } catch (RuntimeException $e) {
            $merchantMessage = $e->getMessage();
        }
    }

    private function chargeRecurring()
    {
        try {
            $heidelBasket   = $this->getHeidelpayBasket();
            $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($this->getHeidelpayB2cCustomer());

            return $this->paymentType->charge(
                $heidelBasket->getAmountTotalGross(),
                $heidelBasket->getCurrencyCode(),
                $this->getHeidelpayReturnUrl(),
                $heidelCustomer,
                $heidelBasket->getOrderId(),
                $this->getHeidelpayMetadata(),
                $heidelBasket
            );
        } catch (HeidelpayApiException $ex) {
            dd($ex);
        }

        return null;
    }
}
