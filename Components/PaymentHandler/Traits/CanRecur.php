<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentHandler\Traits;

use Exception;
use RuntimeException;
use Shopware\Models\Order\Order as SwOrder;
use SwagAboCommerce\Models\Order as AboOrder;
use UnzerPayment\Components\PaymentHandler\Structs\PaymentDataStruct;
use UnzerPayment\Components\PaymentStatusMapper\Exception\NoStatusMapperFoundException;
use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Card;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;
use UnzerSDK\Resources\Recurring;

/**
 * @property BasePaymentType|Card|Paypal|SepaDirectDebit $paymentType
 * @property Payment                                     $payment
 * @property Recurring                                   $recurring
 * @property PaymentDataStruct                           $paymentDataStruct
 */
trait CanRecur
{
    /**
     * @throws UnzerApiException
     */
    public function activateRecurring(string $returnUrl): string
    {
        if (!$this instanceof AbstractUnzerPaymentController) {
            throw new RuntimeException('Trait can only be used in a payment controller context which extends the AbstractUnzerPaymentController class');
        }

        if ($this->paymentType === null) {
            throw new RuntimeException('PaymentType can not be null');
        }

        if (!method_exists($this->paymentType, 'authorize')) {
            throw new RuntimeException('This payment type does not support authorization');
        }

        $this->recurring = $this->paymentType->activateRecurring($returnUrl);

        $this->session->offsetSet('PaymentTypeId', $this->recurring->getPaymentTypeId());

        if (!empty($this->recurring->getRedirectUrl())) {
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
            $statusMapperFactory = $this->container->get('unzer_payment.factory.status_mapper');
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

            /** @var null|SwOrder $newAboOrder */
            $newAboOrder = $this->getModelManager()->getRepository(SwOrder::class)->findOneBy(['number' => $newOrderNumber]);

            if (isset($newAboOrder) && \class_exists(AboOrder::class)) {
                /** @var AboOrder $aboModel */
                $aboModel = $this->getModelManager()->getRepository(AboOrder::class)->find($recurringData['swAboId']);
                $aboModel->run($newAboOrder->getId());

                $this->getModelManager()->flush($aboModel);
            }
        } catch (Exception $ex) {
            $this->getApiLogger()->getPluginLogger()->error($ex->getMessage(), $ex->getTrace());

            $newOrderNumber = '';
        } finally {
            return $newOrderNumber ?? '';
        }
    }
}
