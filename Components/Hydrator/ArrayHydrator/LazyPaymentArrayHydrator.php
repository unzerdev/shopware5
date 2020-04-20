<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Hydrator\ArrayHydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Resources\TransactionTypes\Shipment;
use Symfony\Component\Serializer\Exception\UnsupportedException;

class LazyPaymentArrayHydrator implements ArrayHydratorInterface
{
    /**
     * @param Payment $resource
     */
    public function hydrateArray(AbstractHeidelpayResource $resource): array
    {
        if (get_class($resource) !== Payment::class) {
            throw new UnsupportedException('This resource can not be hydrated as a payment array!');
        }

        $authorization = $resource->getAuthorization();
        $data          = array_merge($resource->expose(), [
            'state' => [
                'name' => $resource->getStateName(),
                'id'   => $resource->getState(),
            ],
            'currency'      => $resource->getCurrency(),
            'authorization' => $authorization ? $authorization->expose() : null,
            'basket'        => $resource->getBasket() ? $resource->getBasket()->expose() : null,
            'customer'      => $resource->getCustomer() ? $resource->getCustomer()->expose() : null,
            'metadata'      => [],
            'type'          => $resource->getPaymentType() ? $resource->getPaymentType()->expose() : null,
            'amount'        => $resource->getAmount() ? $resource->getAmount()->expose() : null,
            'charges'       => [],
            'shipments'     => [],
            'cancellations' => [],
            'transactions'  => [],
        ]);

        if ($authorization !== null) {
            if (!array_key_exists('shortId', $data)) {
                $data['shortId'] = $authorization->getShortId();
            }
            $data['transactions'][] = [
                'type'   => 'authorization',
                'amount' => $authorization->getAmount(),
                'date'   => $authorization->getDate(),
                'id'     => $authorization->getId(),
            ];
        }

        /** @var Charge $metaCharge */
        foreach ($resource->getCharges() as $metaCharge) {
            if (!array_key_exists('shortId', $data)) {
                $data['shortId'] = $metaCharge->getShortId();
            }

            $data['charges'][]      = $metaCharge->expose();
            $data['transactions'][] = [
                'type'    => 'charge',
                'shortId' => $metaCharge->getShortId(),
                'amount'  => $metaCharge->getAmount(),
                'date'    => $metaCharge->getDate(),
                'id'      => $metaCharge->getId(),
            ];
        }

        /** @var Shipment $metaShipment */
        foreach ($resource->getShipments() as $metaShipment) {
            $data['shipments'][]    = $metaShipment->expose();
            $data['transactions'][] = [
                'type'   => 'shipment',
                'amount' => $metaShipment->getAmount(),
                'date'   => $metaShipment->getDate(),
                'id'     => $metaShipment->getId(),
            ];
        }

        /** @var Cancellation $metaCancellation */
        foreach ($resource->getCancellations() as $metaCancellation) {
            $data['cancellations'][] = $metaCancellation->expose();
            $data['transactions'][]  = [
                'type'   => 'cancellation',
                'amount' => $metaCancellation->getAmount(),
                'date'   => $metaCancellation->getDate(),
                'id'     => $metaCancellation->getId(),
            ];
        }

        foreach ($resource->getMetadata()->expose() as $key => $value) {
            $data['metadata'][] = compact('key', 'value');
        }

        return $data;
    }
}
