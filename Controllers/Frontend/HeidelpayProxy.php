<?php

declare(strict_types=1);

use HeidelPayment\Installers\PaymentMethods;
use Psr\Log\LoggerInterface;

class Shopware_Controllers_Frontend_HeidelpayProxy extends Shopware_Controllers_Frontend_Payment
{
    /**
     * Proxy action for redirect payments.
     * Forwards to the correct widget payment controller.
     */
    public function indexAction(): void
    {
        $paymentMethodName = $this->getPaymentShortName();

        if (array_key_exists($paymentMethodName, PaymentMethods::REDIRECT_CONTROLLER_MAPPING)) {
            $controllerName = PaymentMethods::REDIRECT_CONTROLLER_MAPPING[$paymentMethodName];
        }

        if (array_key_exists($paymentMethodName, PaymentMethods::RECURRING_CONTROLLER_MAPPING)) {
            $controllerName = PaymentMethods::RECURRING_CONTROLLER_MAPPING[$paymentMethodName];
        }

        if (empty($controllerName)) {
            $this->redirect([
                'controller' => 'checkout',
                'action'     => 'confirm',
            ]);

            return;
        }

        $this->forward(
            'createPayment',
            $controllerName,
            'widgets',
            $this->request->getParams()
        );
    }

    /**
     * Proxy action for the initial response after redirect (currently PayPal specific)
     */
    public function initialRecurringAction(): void
    {
        $this->forward(
            'recurringFinished',
            PaymentMethods::RECURRING_CONTROLLER_MAPPING[$this->getPaymentShortName()],
            'widgets'
        );
    }

    /**
     * Proxy action for recurring payments.
     * Forwards to the correct widget payment controller.
     */
    public function recurringAction(): void
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        $orderId = (int) $this->request->getParam('orderId');

        if (!$orderId) {
            $this->getLogger()->error('No order id was given!', $this->request->getParams());

            return;
        }

        $paymentName = $this->getModelManager()->getDBALQueryBuilder()
            ->select('scp.name')
            ->from('s_core_paymentmeans', 'scp')
            ->innerJoin('scp', 's_order', 'so', 'so.paymentID = scp.id')
            ->where('so.id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()->fetchColumn();

        if (!$paymentName || PaymentMethods::RECURRING_CONTROLLER_MAPPING[$paymentName] === null) {
            $this->getLogger()->error(sprintf('No payment for order with id %s was found!', $orderId));
            $this->view->assign('success', false);

            return;
        }

        $this->forward('chargeRecurringPayment', PaymentMethods::RECURRING_CONTROLLER_MAPPING[$paymentName], 'widgets');
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->container->get('heidel_payment.logger');
    }
}
