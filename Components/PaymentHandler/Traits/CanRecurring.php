<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentHandler\Traits;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Recurring;
use RuntimeException;
use Shopware\Models\Order\Order as SwOrder;
use SwagAboCommerce\Models\Order as AboOrder;

trait CanRecurring
{
    /**
     * @throws HeidelpayApiException
     */
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

        /** @var Recurring $recurringResult */
        $this->recurring = $this->paymentType->activateRecurring($returnUrl);

        $this->session->offsetSet('heidelRecurringId', $this->recurring->getId());

        if ($this->recurring !== null && !empty($this->recurring->getRedirectUrl())) {
            return $this->recurring->getRedirectUrl();
        }

        return $returnUrl;
    }

    /**
     * @see OrderCronService::createOrder()
     */
    protected function createRecurringOrder(): string
    {
        $paymentStateFactory = $this->container->get('heidel_payment.services.payment_status_factory');

        try {
            $newOrderNumber = $this->saveOrder(
                sprintf('HEIDELPAY_RECURRING_TRANSACTION_%s', $this->payment->getId()),
                $this->payment->getId(),
                $paymentStateFactory->getPaymentStatusId($this->payment)
            );

            /** @var SwOrder $newAboOrder */
            $newAboOrder = $this->getModelManager()->getRepository(SwOrder::class)->findOneBy(['number' => $newOrderNumber]);

            if (isset($newAboOrder)) {
                /** @var AboOrder $aboModel */
                $aboModel = $this->getModelManager()->getRepository(AboOrder::class)->find($this->paymentDataStruct->getAboId());
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
}
