<?php

declare(strict_types=1);

namespace UnzerPayment\Commands;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UnzerPayment\Components\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use UnzerPayment\Subscribers\Model\OrderSubscriber;
use UnzerSDK\Exceptions\UnzerApiException;

class SendShippingCommand extends ShopwareCommand
{
    /** @var ConfigReaderServiceInterface */
    private $configReader;

    /** @var UnzerPaymentApiLoggerServiceInterface */
    private $logger;

    /** @var Connection */
    private $connection;

    /** @var UnzerPaymentClientServiceInterface */
    private $unzerPaymentClientService;

    public function __construct(
        ConfigReaderServiceInterface $configReader,
        UnzerPaymentApiLoggerServiceInterface $logger,
        Connection $connection,
        UnzerPaymentClientServiceInterface $unzerPaymentClientService
    ) {
        $this->configReader              = $configReader;
        $this->logger                    = $logger;
        $this->connection                = $connection;
        $this->unzerPaymentClientService = $unzerPaymentClientService;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('unzer:ship')
            ->setDescription('Sends the shipping notification for matching orders to Unzer.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Starting automatic shipping notification...</comment>');
        $unzerPaymentClient = $this->unzerPaymentClientService->getUnzerPaymentClient('en_GB');
        $orders             = $this->getMatchingOrders();

        if ($unzerPaymentClient === null) {
            $output->writeln('<error>The unzer payment client could not be created</error>');

            return null;
        }

        if (empty($orders)) {
            $output->writeln('<info>No orders where found!</info>');

            return null;
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

            $output->writeln(sprintf('Sending shipping notification for order [%s] with payment-id [%s] and invoice-id [%s]...', $orderId, $paymentId, $invoiceId), OutputInterface::VERBOSITY_VERBOSE);

            try {
                $unzerPaymentClient->ship($paymentId, $invoiceId);
                $this->updateAttribute($orderId);

                ++$notificationCount;
            } catch (UnzerApiException $apiException) {
                $this->logger->logException(sprintf('Unable to send shipping notification for order [%s] with payment-id [%s] and invoice-id [%s]', $orderNumber, $paymentId, $invoiceId), $apiException);

                $output->writeln(sprintf('<error>Unable to send shipping notification for order [%s] with payment-id [%s] and invoice-id [%s]</error>', $orderNumber, $paymentId, $invoiceId));
            }
        }

        $output->writeln('');
        $output->writeln(sprintf('<info>Automatically sent shipping notification for %s order(s)!</info>', $notificationCount));

        return null;
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
                ->andWhere($queryBuilder->expr()->isNull('aAttribute.unzer_payment_shipping_date'))
                ->andWhere('aOrder.status != -1')
                ->andWhere('aDocument.type = :invoiceDocumentType')
            ->setParameter('invoiceDocumentType', ViewBehaviorHandlerInterface::DOCUMENT_TYPE_INVOICE)
            ->setParameter('paymentMeans', OrderSubscriber::ALLOWED_FINALIZE_METHODS, Connection::PARAM_STR_ARRAY);

        /** @var Statement $driverStatement */
        $driverStatement = $queryBuilder->execute();

        return $driverStatement->fetchAll();
    }

    private function updateAttribute(int $orderId): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->update('s_order_attributes')
            ->set('unzer_payment_shipping_date', ':shippingDate')
            ->where('orderID = :orderId')
            ->setParameter('shippingDate', (new DateTimeImmutable())->format(DATE_ATOM))
            ->setParameter('orderId', $orderId)
            ->execute();
    }
}
