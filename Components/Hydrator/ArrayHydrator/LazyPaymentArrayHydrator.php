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

class LazyPaymentArrayHydrator implements ArrayHydratorInterface
{
    /**
     * @param Payment $resource
     */
    public function hydrateArray(AbstractUnzerResource $resource): array
    {
        if (get_class($resource) !== Payment::class) {
            throw new UnsupportedException('This resource can not be hydrated as a payment array!');
        }

        /** @var Authorization $authorization */
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
            /** @var Authorization|Charge $parent */
            $parent = $metaCancellation->getParentResource();

            // Cancellations are not linked to a charge or authorization with Paylater payment methods
            if ($parent instanceof Payment) {
                break;
            }

            $cancellationData       = $metaCancellation->expose();
            $cancellationId         = $parent->getId() . '/' . $metaCancellation->getId();
            $cancellationData['id'] = $cancellationId;

            $data['cancellations'][] = $cancellationData;
            $data['transactions'][]  = [
                'type'   => 'cancellation',
                'amount' => $metaCancellation->getAmount(),
                'date'   => $metaCancellation->getDate(),
                'id'     => $cancellationId,
            ];
        }

        /** @var Cancellation $metaReversal */
        foreach ($resource->getReversals() as $metaReversal) {
            $cancellationData = $metaReversal->expose();

            // There is no link between reversal and an authorization with Paylater payment methods
            $cancellationId         = 's-aut-1/' . $metaReversal->getId();
            $cancellationData['id'] = $cancellationId;

            $data['cancellations'][] = $cancellationData;
            $data['transactions'][]  = [
                'type'   => 'reversal',
                'amount' => $metaReversal->getAmount(),
                'date'   => $metaReversal->getDate(),
                'id'     => $cancellationId,
            ];
        }

        /** @var Cancellation $metaRefund */
        foreach ($resource->getRefunds() as $metaRefund) {
            $cancellationData = $metaRefund->expose();

            // There is no link between refund and a charge with Paylater payment methods
            $cancellationId         = 's-chg-1/' . $metaRefund->getId();
            $cancellationData['id'] = $cancellationId;

            $data['cancellations'][] = $cancellationData;
            $data['transactions'][]  = [
                'type'   => 'refund',
                'amount' => $metaRefund->getAmount(),
                'date'   => $metaRefund->getDate(),
                'id'     => $cancellationId,
            ];
        }

        foreach ($resource->getMetadata()->expose() as $key => $value) {
            $data['metadata'][] = compact('key', 'value');
        }

        return $data;
    }
}
