<?php

declare(strict_types=1);

namespace UnzerPayment\Components\Hydrator\ArrayHydrator;

use Symfony\Component\Serializer\Exception\UnsupportedException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Shipment;

class PaymentArrayHydrator implements ArrayHydratorInterface
{
    /**
     * @param Payment $resource
     */
    public function hydrateArray(AbstractUnzerResource $resource): array
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
            'descriptor'    => $authorization ? $authorization->getDescriptor() : null,
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
            /** @var Charge $charge */
            $charge = $resource->getCharge($metaCharge->getId());

            if (!array_key_exists('shortId', $data)) {
                $data['shortId'] = $charge->getShortId();
            }

            $data['charges'][]      = $charge->expose();
            $data['transactions'][] = [
                'type'    => 'charge',
                'shortId' => $charge->getShortId(),
                'amount'  => $charge->getAmount(),
                'date'    => $charge->getDate(),
                'id'      => $charge->getId(),
            ];
        }

        /** @var Shipment $metaShipment */
        foreach ($resource->getShipments() as $metaShipment) {
            /** @var Shipment $shipment */
            $shipment = $resource->getShipment($metaShipment->getId());

            $data['shipments'][]    = $shipment->expose();
            $data['transactions'][] = [
                'type'   => 'shipment',
                'amount' => $shipment->getAmount(),
                'date'   => $shipment->getDate(),
                'id'     => $shipment->getId(),
            ];
        }

        /** @var Cancellation $metaCancellation */
        foreach ($resource->getCancellations() as $metaCancellation) {
            /** @var Cancellation $cancellation */
            $cancellation = $resource->getCancellation($metaCancellation->getId());
            /** @var Authorization|Charge $parent */
            $parent = $cancellation->getParentResource();

            $cancellationData       = $cancellation->expose();
            $cancellationId         = $parent->getId() . '/' . $cancellation->getId();
            $cancellationData['id'] = $cancellationId;

            $data['cancellations'][] = $cancellationData;
            $data['transactions'][]  = [
                'type'   => 'cancellation',
                'amount' => $cancellation->getAmount(),
                'date'   => $cancellation->getDate(),
                'id'     => $cancellationId,
            ];
        }

        foreach ($resource->getMetadata()->expose() as $key => $value) {
            $data['metadata'][] = compact('key', 'value');
        }

        return $data;
    }
}
