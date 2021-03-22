<?php

declare(strict_types=1);

namespace UnzerPayment\Components\Hydrator\ResourceHydrator;

use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Metadata;
use UnzerSDK\Unzer;

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
        Unzer $unzerPaymentInstance = null,
        string $resourceId = null
    ): AbstractUnzerResource {
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
