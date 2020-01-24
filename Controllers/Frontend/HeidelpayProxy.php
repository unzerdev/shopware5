<?php

declare(strict_types=1);

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use HeidelPayment\Installers\PaymentMethods;

class Shopware_Controllers_Frontend_HeidelpayProxy extends AbstractHeidelpayPaymentController
{
    /**
     * Proxy action for redirect payments.
     * Forwards to the correct widget payment controller.
     */
    public function indexAction(): void
    {
        $paymentMethodName = $this->getPaymentShortName();

        if (array_key_exists($paymentMethodName, PaymentMethods::REDIRECT_CONTROLLER_MAPPING)) {
            $this->forward('createPayment', PaymentMethods::REDIRECT_CONTROLLER_MAPPING[$paymentMethodName], 'widgets');

            return;
        }

        if (array_key_exists($paymentMethodName, PaymentMethods::RECURRING_CONTROLLER_MAPPING)) {
            $this->forward('createPayment', PaymentMethods::RECURRING_CONTROLLER_MAPPING[$paymentMethodName], 'widgets');

            return;
        }

        $this->redirect([
            'controller' => 'checkout',
            'action'     => 'confirm',
        ]);
    }

    public function initialRecurringAction(): void
    {
        $this->forward(
            'recurringFinished',
            PaymentMethods::RECURRING_CONTROLLER_MAPPING[$this->getPaymentShortName()],
            'widgets'
        );
    }

    public function recurringAction(): void
    {
        $orderId = (int) $this->request->getParam('orderId');

        if (!$orderId) {
            $this->getApiLogger()->getPluginLogger()->error(sprintf('No order id was given!', $orderId));
        }

        $paymentName = $this->getModelManager()->getDBALQueryBuilder()
            ->select('scp.name')
            ->from('s_core_paymentmeans', 'scp')
            ->innerJoin('scp', 's_order', 'so', 'so.paymentID = scp.id')
            ->where('so.id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()->fetchColumn();

        if (!$paymentName) {
            $this->getApiLogger()->getPluginLogger()->error(sprintf('No payment for order with id %s was found!', $orderId));
        }

        $this->forward('createRecurringPayment', PaymentMethods::RECURRING_CONTROLLER_MAPPING[$paymentName], 'widgets');
    }
}
