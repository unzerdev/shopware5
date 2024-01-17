<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use UnzerPayment\Installers\PaymentMethods;

class Shopware_Controllers_Frontend_UnzerPaymentProxy extends Shopware_Controllers_Frontend_Payment
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
        if ($this->getOrderNumber() !== null) {
            $temporaryId = $this->getTemporaryIdForOrder($this->getOrderNumber());

            if (empty($temporaryId)) {
                $this->getLogger()->error('Temporary id for order is empty', ['orderNumber' => $this->getOrderNumber()]);

                $this->redirect([
                    'controller' => 'checkout',
                    'action'     => 'confirm',
                ]);

                return;
            }

            $this->redirect([
                'controller' => 'checkout',
                'action'     => 'finish',
                'sUniqueID'  => $temporaryId,
            ]);

            return;
        }

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
            ->execute()->fetchOne();

        if (!$paymentName || PaymentMethods::RECURRING_CONTROLLER_MAPPING[$paymentName] === null) {
            $this->getLogger()->error(sprintf('No payment for order with id %s was found!', $orderId));
            $this->view->assign('success', false);

            return;
        }

        $this->forward('chargeRecurringPayment', PaymentMethods::RECURRING_CONTROLLER_MAPPING[$paymentName], 'widgets');
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->container->get('unzer_payment.logger');
    }

    private function getTemporaryIdForOrder(string $orderNumber): ?string
    {
        $queryBuilder = $this->container->get('dbal_connection')->createQueryBuilder();

        $temporaryId = $queryBuilder
            ->select('temporaryID')
            ->from('s_order')
            ->where($queryBuilder->expr()->eq('ordernumber', ':ordernumber'))
            ->setParameter('ordernumber', $orderNumber)
            ->execute()
            ->fetchOne();

        if (!is_string($temporaryId)) {
            return null;
        }

        return $temporaryId;
    }
}
