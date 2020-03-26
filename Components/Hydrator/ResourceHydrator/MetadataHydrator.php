<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Hydrator\ResourceHydrator;

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
        Heidelpay $heidelpayObj = null,
        string $resourceId = null
    ): AbstractHeidelpayResource {
        $result = new Metadata();

        if ($resourceId !== null && $heidelpayObj !== null) {
            return $heidelpayObj->fetchMetadata($resourceId);
        }

        $result->setShopType(self::SHOP_TYPE);
        $result->setShopVersion($data['shopwareVersion']);
        $result->addMetadata('pluginType','HeidelPayment');

        unset($data['shopwareVersion']);

        foreach ($data as $name => $value) {
            $result->addMetadata($name, $value);
        }

        return $result;
    }
}
