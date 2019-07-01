<?php

namespace HeidelPayment\Services\Heidelpay\DataProviders;

use HeidelPayment\Services\Heidelpay\DataProviderInterface;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Metadata;
use Shopware;

class MetadataProvider implements DataProviderInterface
{
    private const SHOP_TYPE = 'Shopware';

    public function hydrateOrFetch(
        array $data,
        Heidelpay $heidelpayObj,
        string $resourceId = null
    ): AbstractHeidelpayResource {
        $result = new Metadata();

        if ($resourceId !== null) {
            return $heidelpayObj->fetchMetadata($resourceId);
        }

        $result->setShopType(self::SHOP_TYPE);
        $result->setShopVersion(Shopware::VERSION);

        foreach ($data as $name => $value) {
            $result->addMetadata($name, $value);
        }

        return $result;
    }
}
