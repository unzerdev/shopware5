<?php

namespace HeidelPayment\Subscribers\Core;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs as EventArgs;
use HeidelPayment\Services\ConfigReaderServiceInterface;

class PaymentMeans implements SubscriberInterface
{
    /** @var ConfigReaderServiceInterface */
    private $configReader;

    public function __construct(ConfigReaderServiceInterface $configReader)
    {
        $this->configReader = $configReader;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'onFilterPaymentMeans',
        ];
    }

    public function onFilterPaymentMeans(EventArgs $args): void
    {
        $configurationValid = $this->checkConfiguration();
        if ($configurationValid) {
            return;
        }

        /** @var array $paymentMethods */
        $paymentMethods = $args->getReturn();

        foreach ($paymentMethods as $index => $paymentMethod) {
            if (strpos($paymentMethod['name'], 'heidel') !== false) {
                unset($paymentMethods[$index]);
            }
        }

        $args->setReturn($paymentMethods);
    }

    private function checkConfiguration(): bool
    {
        $privateKey = $this->configReader->get('private_key');
        $publicKey  = $this->configReader->get('public_key');

        return !empty($privateKey) && !empty($publicKey);
    }
}
