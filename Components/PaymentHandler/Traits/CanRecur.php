<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentHandler\Traits;

use Exception;
use HeidelPayment\Components\PaymentHandler\Structs\PaymentDataStruct;
use HeidelPayment\Components\PaymentStatusMapper\Exception\NoStatusMapperFoundException;
use HeidelPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Card;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use heidelpayPHP\Resources\PaymentTypes\SepaDirectDebit;
use heidelpayPHP\Resources\Recurring;
use RuntimeException;
use Shopware\Models\Order\Order as SwOrder;
use SwagAboCommerce\Models\Order as AboOrder;

/**
 * @property BasePaymentType|Card|Paypal|SepaDirectDebit $paymentType
 * @property Payment $payment
 * @property Recurring $recurring
 * @property PaymentDataStruct $paymentDataStruct
 */
trait CanRecur
{
    public function activateRecurring(string $returnUrl): string
    {
        if (!$this instanceof AbstractHeidelpayPaymentController) {
            throw new RuntimeException('Trait can only be used in a payment controller context which extends the AbstractHeidelpayPaymentController class');
        }

        if ($this->paymentType === null) {
            throw new RuntimeException('PaymentType can not be null');
        }

        if (!method_exists($this->paymentType, 'authorize')) {
            throw new RuntimeException('This payment type does not support authorization');
        }

        $this->recurring = $this->paymentType->activateRecurring($returnUrl);

        $this->session->offsetSet('PaymentTypeId', $this->recurring->getPaymentTypeId());

        if ($this->recurring !== null && !empty($this->recurring->getRedirectUrl())) {
            return $this->recurring->getRedirectUrl();
        }

        return $returnUrl;
    }

    /** @see OrderCronService::createOrder() */
    protected function createRecurringOrder(): string
    {
        if (!$this->payment) {
            $this->getApiLogger()->getPluginLogger()->error('The payment could not be created');

            return '';
        }

        try {
            $statusMapperFactory = $this->container->get('heidel_payment.factory.status_mapper');
            $statusMapper        = $statusMapperFactory->getStatusMapper($this->payment->getPaymentType());
            $targetPaymentStatus = $statusMapper->getTargetPaymentStatus($this->payment);
        } catch (NoStatusMapperFoundException | StatusMapperException $ex) {
            $this->getApiLogger()->getPluginLogger()->error($ex->getMessage(), $ex->getTrace());

            return '';
        }

        $recurringData = $this->paymentDataStruct->getRecurringData();

        try {
            $newOrderNumber = $this->saveOrder($this->payment->getOrderId(), $this->payment->getId(), $targetPaymentStatus);

            if (empty($newOrderNumber)) {
                $this->getApiLogger()->getPluginLogger()->error('Order for payment could not be created', [
                    'payment' => json_encode($this->payment),
                ]);

                return '';
            }

            /** @var SwOrder $newAboOrder */
            $newAboOrder = $this->getModelManager()->getRepository(SwOrder::class)->findOneBy(['number' => $newOrderNumber]);

            if (isset($newAboOrder)) {
                /** @var AboOrder $aboModel */
                $aboModel = $this->getModelManager()->getRepository(AboOrder::class)->find($recurringData['swAboId']);
                $aboModel->run($newAboOrder->getId());

                $this->getModelManager()->flush($aboModel);
            }
        } catch (Exception $ex) {
            $this->getApiLogger()->getPluginLogger()->error($ex->getMessage(), $ex->getTrace());

            $newOrderNumber = '';
        } finally {
            return $newOrderNumber ?: '';
        }
    }
}
