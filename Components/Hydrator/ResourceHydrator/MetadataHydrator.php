<?php

declare(strict_types=1);

namespace UnzerPayment\Components\Hydrator\ResourceHydrator;

use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Metadata;

class MetadataHydrator implements ResourceHydratorInterface
{
    private const SHOP_TYPE = 'Shopware';

    /**
     * {@inheritdoc}
     *
     * @return Metadata
     */
    public function hydrateOrFetch(
        array $data,
        Heidelpay $unzerPaymentInstance = null,
        string $resourceId = null
    ): AbstractHeidelpayResource {
        $result = new Metadata();

        if ($resourceId !== null && $unzerPaymentInstance !== null) {
            return $unzerPaymentInstance->fetchMetadata($resourceId);
        }

        $result->setShopType(self::SHOP_TYPE);
        $result->setShopVersion($data['shopwareVersion']);
        $result->addMetadata('pluginType', 'UnzerPayment');

        unset($data['shopwareVersion']);

        foreach ($data as $name => $value) {
            $result->addMetadata($name, $value);
        }

        return $result;
    }
}
