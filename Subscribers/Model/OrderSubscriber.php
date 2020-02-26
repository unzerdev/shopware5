<?php

declare(strict_types=1);

namespace HeidelPayment\Subscribers\Model;

use DateTimeImmutable;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use HeidelPayment\Components\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use HeidelPayment\Installers\PaymentMethods;
use HeidelPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use HeidelPayment\Services\DependencyProvider\DependencyProviderServiceInterface;
use HeidelPayment\Services\HeidelpayApiLogger\HeidelpayApiLoggerServiceInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use Shopware\Models\Order\Document\Document;
use Shopware\Models\Order\Order;

class OrderSubscriber implements EventSubscriber
{
    public const SUPPORTED_PAYMENT_METHOD_NAMES = [
        PaymentMethods::PAYMENT_NAME_INVOICE_FACTORING,
        PaymentMethods::PAYMENT_NAME_INVOICE_GUARANTEED,
    ];

    /** @var DependencyProviderServiceInterface */
    private $dependencyProvider;

    /**
     * Since this class requires both (ApiService and ConfigService) which have a dependency it's required
     * to use the dependency provider to avoid an Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * while initializing this subscriber.
     */
    public function __construct(DependencyProviderServiceInterface $dependencyProvider)
    {
        $this->dependencyProvider = $dependencyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postUpdate,
        ];
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        if (!$args->getEntity() instanceof Order) {
            return;
        }

        /** @var ConfigReaderServiceInterface $configReader */
        $configReader = $this->dependencyProvider->get('heidel_payment.services.config_reader');

        /** @var Order $order */
        $order                  = $args->getEntity();
        $orderStatusForShipping = $configReader->get('shipping_status', $order->getShop()->getId());

        if (empty($orderStatusForShipping)) {
            return;
        }

        if ($order->getAttribute()->getHeidelpayShippingDate() !== null
            || $order->getOrderStatus()->getId() !== $orderStatusForShipping
            || !in_array($order->getPayment()->getName(), self::SUPPORTED_PAYMENT_METHOD_NAMES, false)) {
            return;
        }

        /** @var null|Document $invoiceDocument */
        $invoiceDocument = $order->getDocuments()->filter(static function (Document $entry) {
            return (int) $entry->getType() === ViewBehaviorHandlerInterface::DOCUMENT_TYPE_INVOICE;
        })->last();

        if (!$invoiceDocument) {
            return;
        }

        /** @var HeidelpayApiLoggerServiceInterface $apiLogger */
        $apiLogger       = $this->dependencyProvider->get('heidel_payment.services.api_logger');
        $heidelpayClient = new Heidelpay($configReader->get('private_key'), $order->getShop()->getLocale()->getLocale());

        try {
            $heidelpayClient->ship($order->getTemporaryId(), $invoiceDocument->getDocumentId());

            $orderAttribute = $order->getAttribute();
            $orderAttribute->setHeidelpayShippingDate(new DateTimeImmutable());

            $args->getEntityManager()->flush($orderAttribute);
        } catch (HeidelpayApiException $apiException) {
            $apiLogger->logException(sprintf('Unable to send shipping notification for order [%s] with payment-id [%s]', $order->getNumber(), $order->getTemporaryId()), $apiException);
        }
    }
}
