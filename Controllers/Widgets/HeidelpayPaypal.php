<?php

declare(strict_types=1);

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use heidelpayPHP\Resources\Recurring;
use heidelpayPHP\Resources\TransactionTypes\Charge;

class Shopware_Controllers_Widgets_HeidelpayPaypal extends AbstractHeidelpayPaymentController
{
    /** @var Paypal */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        $this->paymentType = $this->heidelpayClient->createPaymentType(new Paypal());
        $this->paymentType->setParentResource($this->heidelpayClient);
        $this->session->offsetSet('PaymentTypeId', $this->paymentType->getId());

        if (!$this->paymentType) {
            $this->handleCommunicationError();

            return;
        }

        $returnUrl    = $this->getHeidelpayReturnUrl();
        $heidelBasket = $this->getHeidelpayBasket();
        $isRecurring  = $heidelBasket->getSpecialParams()['isAbo'] ?: false;

        if ($isRecurring) {
            $this->recurringPurchase($returnUrl);
        } else {
            $this->recurringPurchase($returnUrl);
//            $this->singlePurchase($heidelBasket, $returnUrl);
        }
    }

    public function createRecurringPaymentAction()
    {
        $orderId           = (int) $this->request->getParam('orderId');
        $this->paymentType = $this->heidelpayClient->createPaymentType(new Paypal());
        $basketAmount      = (float) $this->session->offsetGet('sBasketAmount');
        $orderData         = $this->getOrderDataById($orderId);
        $this->paymentType->setParentResource($this->heidelpayClient);

        $this->paymentType->charge(
            $basketAmount,
            $orderData['currency'],
            $this->getChargeRecurringUrl(),
            null,
            null,
            null,
            null,
            null,
            null,
            $orderData['transactionId']
        );
    }

    public function paypalFinishedAction()
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

                $this->session->offsetSet('heidelPaymentId', $chargeResult->getPaymentId());
                $this->redirect($chargeResult->getReturnUrl());
            }
        } catch (HeidelpayApiException $e) {
            $merchantMessage = $e->getMerchantMessage();
            $clientMessage   = $e->getClientMessage();
            $this->getApiLogger()->logException('Error while creating PayPal recurring payment', $apiException);

            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        } catch (RuntimeException $e) {
            $merchantMessage = $e->getMessage();
        }
    }

    private function recurringPurchase(string $returnUrl): void
    {
        /** @var Recurring $recurring */
        $recurring = $this->heidelpayClient->activateRecurringPayment(
            $this->paymentType->getId(),
            $this->getinitialRecurringUrl()
        );

        if (!$recurring) {
//                TODO: throw error
            return;
        }

        if (empty($recurring->getRedirectUrl()) && $recurring->isSuccess()) {
            $this->redirect($returnUrl);
        } elseif (!empty($recurring->getRedirectUrl()) && $recurring->isPending()) {
            $this->redirect($recurring->getRedirectUrl());
        }
    }

    private function singlePurchase(Basket $heidelBasket, string $returnUrl): void
    {
        $heidelCustomer = $this->getHeidelpayB2cCustomer();
        $heidelMetadata = $this->getHeidelpayMetadata();
        $bookingMode    = $this->container->get('heidel_payment.services.config_reader')->get('paypal_bookingmode');

        try {
            $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($heidelCustomer);

            if ($bookingMode === BookingMode::CHARGE) {
                $result = $this->paymentType->charge(
                    $heidelBasket->getAmountTotalGross(),
                    $heidelBasket->getCurrencyCode(),
                    $returnUrl,
                    $heidelCustomer,
                    $heidelBasket->getOrderId(),
                    $heidelMetadata,
                    $heidelBasket
                );
            } else {
                $result = $this->paymentType->authorize(
                    $heidelBasket->getAmountTotalGross(),
                    $heidelBasket->getCurrencyCode(),
                    $returnUrl,
                    $heidelCustomer,
                    $heidelBasket->getOrderId(),
                    $heidelMetadata,
                    $heidelBasket
                );
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating PayPal payment', $apiException);

            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
        $this->redirect($result->getPayment()->getRedirectUrl());
    }

    private function chargeRecurring()
    {
        try {
            $heidelBasket   = $this->getHeidelpayBasket();
            $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($this->getHeidelpayB2cCustomer());

            $charge = $this->paymentType->charge(
                $heidelBasket->getAmountTotalGross(),
                $heidelBasket->getCurrencyCode(),
                $this->getHeidelpayReturnUrl(),
                $heidelCustomer,
                $heidelBasket->getOrderId(),
                $this->getHeidelpayMetadata(),
                $heidelBasket
            );
//
//            echo '<pre>';
//            print_r($charge);
//            echo '</pre>';
//            exit();

            return $charge;

        } catch (HeidelpayApiException $ex) {
//            dd($ex);

            echo '<pre>';
            print_r($ex);
            echo '</pre>';
            exit();
        }

        return null;
    }

    private function getinitialRecurringUrl()
    {
        return $this->get('router')->assemble([
            'controller' => 'HeidelpayProxy',
            'action'     => 'initialRecurringPaypal',
        ]);
    }

    private function getChargeRecurringUrl()
    {
        return $this->get('router')->assemble([
            'controller' => 'HeidelpayProxy',
            'action'     => 'chargeRecurringPaypal',
        ]);
    }

    private function getOrderDataById(int $orderId): array
    {
        return $this->getModelManager()->getDBALQueryBuilder()
            ->select('transactionId, currency')
            ->from('s_order', 'so')
            ->where('so.id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()->fetchAll();
    }
}
