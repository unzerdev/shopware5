<?php

namespace HeidelPayment\Commands;

use Doctrine\DBAL\Connection;
use HeidelPayment\Installers\PaymentMethods;
use HeidelPayment\Services\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendShippingCommand extends ShopwareCommand
{
    private const SUPPORTED_PAYMENT_METHOD_NAMES = [
        PaymentMethods::PAYMENT_NAME_PRE_PAYMENT,
        PaymentMethods::PAYMENT_NAME_INVOICE_FACTORING,
        PaymentMethods::PAYMENT_NAME_INVOICE_GUARANTEED,
        PaymentMethods::PAYMENT_NAME_SEPA_DIRECT_DEBIT_GUARANTEED,
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('heidelpay:ship')
            ->setDescription('Sends the shipping notification for matching orders to heidelpay.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $configService = $this->container->get('heidel_payment.services.config_reader');
        $privateKey    = $configService->get('private_key');

        $heidelpayClient = new Heidelpay($privateKey, 'en_GB');
        $logger          = $this->container->get('heidel_payment.services.api_logger');

        $orders      = $this->getMatchingOrders();
        $progressBar = new ProgressBar($output, count($orders));
        foreach ($orders as $order) {
            $paymentId   = $order['paymentId'];
            $orderNumber = $order['number'];
            $orderId     = (int) $order['id'];
            $invoiceId   = $order['invoiceId'];

            try {
                $shippingResult = $heidelpayClient->ship($paymentId, $invoiceId);
                $logger->logResponse(sprintf('Sent shipping notification for order [%s] with payment-id [%s]', $orderNumber, $paymentId), $shippingResult);

                $this->updateAttribute($orderId);
            } catch (HeidelpayApiException $apiException) {
                $logger->logException(sprintf('Unable to send shipping notification for order [%s] with payment-id [%s]', $orderNumber, $paymentId), $apiException);

                $output->write(sprintf('<error>Unable to send shipping notification for order [%s] with payment-id [%s]</error>', $orderNumber, $paymentId), true, OutputInterface::VERBOSITY_VERBOSE);
            }

            $progressBar->advance();
        }
    }

    private function getMatchingOrders()
    {
        $orderStatusId = (int) $this->container->get('heidel_payment.services.config_reader')->get('shipping_status');

        $queryBuilder = $this->container->get('dbal_connection')->createQueryBuilder();
        $queryBuilder->select('aOrder.temporaryID AS paymentId, aOrder.ordernumber as number, aDocument.docID as invoiceId, aOrder.id as id')
            ->from('s_order', 'aOrder')
                ->innerJoin('aOrder', 's_core_paymentmeans', 'aPayment', 'aOrder.paymentID = aPayment.id')
                ->innerJoin('aOrder', 's_order_attributes', 'aAttribute', 'aOrder.id = aAttribute.orderID')
                ->leftJoin('aOrder', 's_order_documents', 'aDocument', 'aOrder.id = aDocument.orderID')
            ->where('aPayment.name IN (:paymentMeans)')
                ->andWhere($queryBuilder->expr()->isNull('aAttribute.heidelpay_shipping_date'))
                ->andWhere('aOrder.status = :statusId')
                ->andWhere('aDocument.type = :invoiceDocumentType')
            ->setParameter('statusId', $orderStatusId)
            ->setParameter('invoiceDocumentType', ViewBehaviorHandlerInterface::DOCUMENT_TYPE_INVOICE)
            ->setParameter('paymentMeans', self::SUPPORTED_PAYMENT_METHOD_NAMES, Connection::PARAM_STR_ARRAY);

        return $queryBuilder->execute()->fetchAll();
    }

    private function updateAttribute(int $orderId): void
    {
        $queryBuilder = $this->container->get('dbal_connection')->createQueryBuilder();
        $queryBuilder->update('s_order_attributes')
            ->set('heidelpay_shipping_date', ':shippingDate')
            ->where('orderID = :orderId')
            ->setParameter('shippingDate', (new \DateTimeImmutable())->format('YYYY-dd-mm'))
            ->setParameter('orderId', $orderId)
            ->execute();
    }
}
