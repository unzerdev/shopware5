<?php

namespace HeidelPayment\Commands;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use HeidelPayment\Services\ConfigReaderServiceInterface;
use HeidelPayment\Services\HeidelpayApiLoggerServiceInterface;
use HeidelPayment\Services\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use HeidelPayment\Subscribers\Model\OrderSubscriber;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendShippingCommand extends ShopwareCommand
{
    /** @var ConfigReaderServiceInterface */
    private $configReader;

    /** @var HeidelpayApiLoggerServiceInterface */
    private $logger;

    /** @var Connection */
    private $connection;

    public function __construct(ConfigReaderServiceInterface $configReader, HeidelpayApiLoggerServiceInterface $apiLoggerService, Connection $connection)
    {
        $this->configReader = $configReader;
        $this->logger       = $apiLoggerService;
        $this->connection   = $connection;

        parent::__construct();
    }

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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Starting automatic shipping notification...</comment>');

        $orders = $this->getMatchingOrders();
        if (empty($orders)) {
            $output->writeln('<info>No orders where found!</info>');

            return;
        }

        $notificationCount = 0;
        foreach ($orders as $order) {
            $paymentId         = $order['paymentId'];
            $orderNumber       = $order['number'];
            $orderId           = (int) $order['id'];
            $invoiceId         = $order['invoiceId'];
            $shopId            = $order['shopId'];
            $statusId          = (int) $order['statusId'];
            $orderStatusConfig = $this->configReader->get('shipping_status', $shopId);

            if (empty($orderStatusConfig) || (int) $orderStatusConfig !== $statusId) {
                continue;
            }

            $privateKey      = $this->configReader->get('private_key', $shopId);
            $heidelpayClient = new Heidelpay($privateKey, 'en_GB');

            $output->writeln(sprintf('Sending shipping notification for order [%s] with payment-id [%s] and invoice-id [%s]...', $orderId, $paymentId, $invoiceId), OutputInterface::VERBOSITY_VERBOSE);

            try {
                $shippingResult = $heidelpayClient->ship($paymentId, $invoiceId);
                $this->logger->logResponse(sprintf('Sent shipping notification for order [%s] with payment-id [%s] and invoice id [%s]', $orderNumber, $paymentId, $invoiceId), $shippingResult);
                $this->updateAttribute($orderId);

                ++$notificationCount;
            } catch (HeidelpayApiException $apiException) {
                $this->logger->logException(sprintf('Unable to send shipping notification for order [%s] with payment-id [%s] and invoice-id [%s]', $orderNumber, $paymentId, $invoiceId), $apiException);

                $output->writeln(sprintf('<error>Unable to send shipping notification for order [%s] with payment-id [%s] and invoice-id [%s]</error>', $orderNumber, $paymentId, $invoiceId));
            }
        }

        $output->writeln('');
        $output->writeln(sprintf('<info>Automatically sent shipping notification for %s order(s)!</info>', $notificationCount));
    }

    private function getMatchingOrders(): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('aOrder.temporaryID AS paymentId, aOrder.ordernumber as number, aDocument.docID as invoiceId, aOrder.id as id, aOrder.subshopID as shopId, aOrder.status as statusId')
            ->from('s_order', 'aOrder')
                ->innerJoin('aOrder', 's_core_paymentmeans', 'aPayment', 'aOrder.paymentID = aPayment.id')
                ->innerJoin('aOrder', 's_order_attributes', 'aAttribute', 'aOrder.id = aAttribute.orderID')
                ->leftJoin('aOrder', 's_order_documents', 'aDocument', 'aOrder.id = aDocument.orderID')
            ->where('aPayment.name IN (:paymentMeans)')
                ->andWhere($queryBuilder->expr()->isNull('aAttribute.heidelpay_shipping_date'))
                ->andWhere('aOrder.status != -1')
                ->andWhere('aDocument.type = :invoiceDocumentType')
            ->setParameter('invoiceDocumentType', ViewBehaviorHandlerInterface::DOCUMENT_TYPE_INVOICE)
            ->setParameter('paymentMeans', OrderSubscriber::SUPPORTED_PAYMENT_METHOD_NAMES, Connection::PARAM_STR_ARRAY);

        return $queryBuilder->execute()->fetchAll();
    }

    private function updateAttribute(int $orderId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->update('s_order_attributes')
            ->set('heidelpay_shipping_date', ':shippingDate')
            ->where('orderID = :orderId')
            ->setParameter('shippingDate', (new DateTimeImmutable())->format(DATE_ATOM))
            ->setParameter('orderId', $orderId)
            ->execute();
    }
}
