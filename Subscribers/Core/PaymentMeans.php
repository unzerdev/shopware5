<?php

declare(strict_types=1);

namespace UnzerPayment\Subscribers\Core;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs as EventArgs;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;

class PaymentMeans implements SubscriberInterface
{
    private ConfigReaderServiceInterface $configReader;

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
        /** @var array $paymentMethods */
        $paymentMethods = $args->getReturn();

        $paymentMethods = array_filter($paymentMethods, static function (array $paymentMethod) {
            return !(stripos($paymentMethod['name'], 'unzer') !== false && stripos($paymentMethod['name'], 'giropay') !== false);
        });

        $configurationValid = $this->checkConfiguration();

        if (!$configurationValid) {
            foreach ($paymentMethods as $index => $paymentMethod) {
                if (strpos($paymentMethod['name'], 'unzer') !== false) {
                    unset($paymentMethods[$index]);
                }
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
