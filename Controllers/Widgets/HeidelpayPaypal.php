<?php

declare(strict_types=1);

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use heidelpayPHP\Resources\Recurring;
use SwagAboCommerce\Models\Order as OrderModel;

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
            $this->singlePurchase($heidelBasket, $returnUrl);
        }
    }

    public function createRecurringPaymentAction(): void
    {
        $orderId           = (int) $this->request->getParam('orderId');
        $this->paymentType = $this->heidelpayClient->createPaymentType(new Paypal());
        $basketAmount      = (float) $this->session->offsetGet('sBasketAmount');
        $orderData         = $this->getOrderDataById($orderId);
        $aboId             = $this->getAboIdByOrderId($orderId);
        $this->paymentType->setParentResource($this->heidelpayClient);

        if (!array_key_exists(0, $orderData)) {
            return;
        }

        if ($basketAmount === 0.0) {
            $basketAmount = (float) $orderData[0]['invoice_amount'];
        }

        try {
            $this->paymentType->charge(
                $basketAmount,
                $orderData[0]['currency'],
                $this->getChargeRecurringUrl(),
                null,
                null,
                null,
                null,
                null,
                null,
                $orderData[0]['transactionId']
            );

            $this->view->assign([
                'success' => true,
                'data'    => [
                    'orderNumber' => $orderData[0]['ordernumber'],
                ],
            ]);

            /** @var OrderModel $order */
            $order = $this->getModelManager()->getRepository(OrderModel::class)->find($aboId);

            $order->run($orderId);
            $this->getModelManager()->flush($order);
        } catch (HeidelpayApiException $ex) {
            $this->getApiLogger()->logException($ex->getMessage(), $ex);
        }
    }

    public function paypalFinishedAction(): void
    {
        $session       = $this->container->get('session');
        $paymentTypeId = $session->offsetGet('PaymentTypeId');

        try {
            $heidelpayClient   = $this->container->get('heidel_payment.services.api_client')->getHeidelpayClient();
            $this->paymentType = $heidelpayClient->fetchPaymentType($paymentTypeId);

            if ($this->paymentType instanceof Paypal && $this->paymentType->isRecurring()) {
                $heidelBasket   = $this->getHeidelpayBasket();
                $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($this->getHeidelpayB2cCustomer());

                $chargeResult = $this->paymentType->charge(
                    $heidelBasket->getAmountTotalGross(),
                    $heidelBasket->getCurrencyCode(),
                    $this->getHeidelpayReturnUrl(),
                    $heidelCustomer,
                    $heidelBasket->getOrderId(),
                    $this->getHeidelpayMetadata(),
                    $heidelBasket
                );

                if (!$chargeResult) {
                    $this->getApiLogger()->getPluginLogger()->warning('PayPal is not chargeable for basket', [$heidelBasket->jsonSerialize()]);
                    $this->handleCommunicationError();
                }

                $this->session->offsetSet('heidelPaymentId', $chargeResult->getPaymentId());
                $this->redirect($chargeResult->getReturnUrl());
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating PayPal recurring payment', $apiException);

            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }
    }

    private function recurringPurchase(string $returnUrl): void
    {
        try {
            /** @var Recurring $recurring */
            $recurring = $this->heidelpayClient->activateRecurringPayment(
                $this->paymentType->getId(),
                $this->getinitialRecurringUrl()
            );

            if (!$recurring) {
                $this->getApiLogger()->getPluginLogger()->warning('Recurring could not be activated for basket', $heidelBasket);

                $this->view->assign([
                    'success'     => false,
                    'redirectUrl' => $this->getHeidelpayErrorUrlFromSnippet(
                        'frontend/heidelpay/checkout/confirm',
                        'recurringError'),
                ]);

                return;
            }

            if (empty($recurring->getRedirectUrl()) && $recurring->isSuccess()) {
                $this->redirect($returnUrl);
            } elseif (!empty($recurring->getRedirectUrl()) && $recurring->isPending()) {
                $this->redirect($recurring->getRedirectUrl());
            }
        } catch (HeidelpayApiException $ex) {
            $this->getApiLogger()->logException($ex->getMessage(), $ex);
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

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $this->redirect($result->getPayment()->getRedirectUrl());
        }
    }

    private function getinitialRecurringUrl()
    {
        return $this->get('router')->assemble([
            'module'     => 'frontend',
            'controller' => 'HeidelpayProxy',
            'action'     => 'initialRecurringPaypal',
        ]);
    }

    private function getOrderDataById(int $orderId): array
    {
        return $this->getModelManager()->getDBALQueryBuilder()
            ->select(['ordernumber', 'transactionId', 'currency', 'invoice_amount'])
            ->from('s_order', 'so')
            ->where('so.id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()->fetchAll();
    }

    private function getAboIdByOrderId(int $orderId)
    {
        return $this->getModelManager()->getDBALQueryBuilder()
            ->select('id')
            ->from('s_plugin_swag_abo_commerce_orders')
            ->where('last_order_id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()->fetchColumn();
    }
}
