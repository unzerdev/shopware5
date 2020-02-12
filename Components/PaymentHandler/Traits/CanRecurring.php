<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentHandler\Traits;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use RuntimeException;
use Shopware\Models\Order\Order as SwOrder;
use SwagAboCommerce\Models\Order as AboOrder;

trait CanRecurring
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

    /**
     * @see OrderCronService::createOrder()
     */
    protected function createRecurringOrder(): string
    {
        if (!$this->payment) {
            $this->getApiLogger()->getPluginLogger()->error('The payment could not be created');

            return '';
        }

        $paymentStateFactory = $this->container->get('heidel_payment.services.payment_status_factory');
        $recurringData       = $this->paymentDataStruct->getRecurringData();

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
                $aboModel = $this->getModelManager()->getRepository(AboOrder::class)->find($recurringData['swAboId']);
                $aboModel->run($newAboOrder->getId());

                $this->getModelManager()->flush($aboModel);
            }
        } catch (ORMException | OptimisticLockException $exception) {
            $this->getApiLogger()->getPluginLogger()->warning($exception->getMessage(), $exception->getTrace());
            $this->view->assign('success', false);
            $newOrderNumber = '';
        } finally {
            return $newOrderNumber ?: '';
        }
    }
}
