<?php

declare(strict_types=1);

namespace HeidelPayment\Controllers;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use HeidelPayment\Components\Payment\HeidelPaymentStruct\HeidelPaymentStruct;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Card;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Shopware\Models\Order\Order as SwOrder;
use SwagAboCommerce\Models\Order as AboOrder;
use SwagAboCommerce\Services\OrderCronService;

abstract class AbstractRecurringPaymentController extends AbstractHeidelpayPaymentController
{
    public function createRecurringPaymentAction(): void
    {
        $basketAmount = (float) $this->session->offsetGet('sBasketAmount');
        $orderId      = (int) $this->request->getParam('orderId');
        $order        = $this->getOrderDataById($orderId);
        $abo          = $this->getAboByOrderId($orderId);

        if (!array_key_exists(0, $order) || !array_key_exists(0, $abo)) {
            $this->view->assign([
                'success' => false,
            ]);

            return;
        }

        $abo           = $abo[0];
        $order         = $order[0];
        $originalOrder = $this->getOrderDataById((int) $abo['order_id']);

        if (!array_key_exists(0, $originalOrder)) {
            $this->view->assign([
                'success' => false,
            ]);

            return;
        }

        $originalOrder = $originalOrder[0];
        $transactionId = $originalOrder['transactionID'];

        if ($basketAmount === 0.0) {
            $basketAmount = (float) $order['invoice_amount'];
        }

        if (!$order['transactionID']) {
            $this->view->assign([
                'success' => false,
            ]);

            return;
        }

        $payment = $this->getPaymentByTransactionId($order['transactionID']);

        if (!$payment) {
            $this->view->assign([
                'success' => false,
            ]);

            return;
        }

        $this->paymentType = $this->getPaymentTypeByPayment($payment);

        if (!$this->paymentType) {
            $this->view->assign([
                'success' => false,
            ]);

            return;
        }

        $paymentStruct = new HeidelPaymentStruct($basketAmount, $order['currency'], $this->getChargeRecurringUrl());
        $paymentStruct->fromArray([
            'customer'         => $payment->getCustomer(),
            'orderId'          => $payment->getOrderId(),
            'metaData'         => $payment->getMetadata(),
            'paymentReference' => (string) $transactionId,
        ]);

        try {
            $result = $this->handleRecurringPayment($paymentStruct);

            $orderNumber = $this->handleRecurringOrderCreation($result, (int) $abo['id']);
        } catch (HeidelpayApiException $ex) {
            $this->getApiLogger()->logException($ex->getMessage(), $ex);
        }

        $this->view->assign([
            'success' => isset($orderNumber),
            'data'    => [
                'orderNumber' => $orderNumber ?: '',
            ],
        ]);
    }

    abstract protected function handleRecurringPayment(HeidelPaymentStruct $heidelPaymentStruct);

    /**
     * @param Authorization|Charge $result
     *
     * @see OrderCronService::createOrder()
     */
    protected function handleRecurringOrderCreation($result, int $aboId): string
    {
        $paymentStateFactory = $this->container->get('heidel_payment.services.payment_status_factory');

        try {
            $newOrderNumber = $this->saveOrder(
                'heidel_transaction_' . (string) rand(0, 999999),
                $result->getPayment()->getId(),
                $paymentStateFactory->getPaymentStatusId($result->getPayment())
            );

            /** @var SwOrder $newAboOrder */
            $newAboOrder = $this->getModelManager()->getRepository(SwOrder::class)->findOneBy(['number' => $newOrderNumber]);

            if (isset($newAboOrder)) {
                /** @var AboOrder $aboModel */
                $aboModel = $this->getModelManager()->getRepository(AboOrder::class)->find($aboId);
                $aboModel->run($newAboOrder->getId());

                $this->getModelManager()->flush($aboModel);
            }
        } catch (ORMException $ORMException) {
            $this->getApiLogger()->getPluginLogger()->warning($ORMException->getMessage(), $ORMException->getTrace());
        } catch (OptimisticLockException $lockException) {
            $this->getApiLogger()->getPluginLogger()->warning($lockException->getMessage(), $lockException->getTrace());
        }

        return $newOrderNumber ?: '';
    }

    protected function getChargeRecurringUrl()
    {
        return $this->get('router')->assemble([
            'module'     => 'frontend',
            'controller' => 'HeidelpayProxy',
            'action'     => 'recurring',
        ]);
    }

    protected function getinitialRecurringUrl()
    {
        return $this->get('router')->assemble([
            'module'     => 'frontend',
            'controller' => 'HeidelpayProxy',
            'action'     => 'initialRecurringPaypal',
        ]);
    }

    protected function getOrderDataById(int $orderId): array
    {
        return $this->getModelManager()->getDBALQueryBuilder()
            ->select('*')
            ->from('s_order')
            ->where('id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function getAboByOrderId(int $orderId): array
    {
        return $this->getModelManager()->getDBALQueryBuilder()
            ->select('*')
            ->from('s_plugin_swag_abo_commerce_orders')
            ->where('last_order_id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function getPaymentByTransactionId(string $transactionId): ?Payment
    {
        if (!$transactionId) {
            return null;
        }

        try {
            $payment = $this->heidelpayClient->fetchPaymentByOrderId($transactionId);
        } catch (HeidelpayApiException $heidelpayApiException) {
            $this->getApiLogger()->logException($heidelpayApiException->getMessage(), $heidelpayApiException);
        }

        return $payment ?: null;
    }

    protected function getPaymentTypeByPayment(Payment $payment): ?BasePaymentType
    {
        try {
            $paymentType = $this->heidelpayClient->fetchPaymentType($payment->getPaymentType()->getId());
            $paymentType->setParentResource($this->heidelpayClient);
        } catch (HeidelpayApiException $heidelpayApiException) {
            $this->getApiLogger()->logException($heidelpayApiException->getMessage(), $heidelpayApiException);
        }

        return $paymentType ?: null;
    }

    protected function getPaymentTypeByTransactionId(string $transactionId): ?Card
    {
        try {
            $paymentTypeId = $this->heidelpayClient->fetchPaymentByOrderId($transactionId)->getPaymentType()->getId();
            $paymentType   = $this->heidelpayClient->fetchPaymentType($paymentTypeId);
            $paymentType->setParentResource($this->heidelpayClient);
        } catch (HeidelpayApiException $heidelpayApiException) {
            $this->getApiLogger()->logException($heidelpayApiException->getMessage(), $heidelpayApiException);
        }

        return $paymentType ?: null;
    }
}
