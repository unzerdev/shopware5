<?php

namespace HeidelPayment\Services\Heidelpay\ArrayHydrators;

use HeidelPayment\Services\Heidelpay\ArrayHydratorInterface;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Resources\TransactionTypes\Shipment;
use Symfony\Component\Serializer\Exception\UnsupportedException;

class PaymentArrayHydrator implements ArrayHydratorInterface
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
            $data['transactions'][] = [
                'type'   => 'authorization',
                'amount' => $authorization->getAmount(),
                'date'   => $authorization->getDate(),
                'id'     => $authorization->getId(),
            ];
        }

        /** @var Charge $charge */
        foreach ($resource->getCharges() as $charge) {
            $data['charges'][]      = $charge->expose();
            $data['transactions'][] = [
                'type'   => 'charge',
                'amount' => $charge->getAmount(),
                'date'   => $charge->getDate(),
                'id'     => $charge->getId(),
            ];
        }

        /** @var Shipment $shipment */
        foreach ($resource->getShipments() as $shipment) {
            $data['shipments'][]    = $shipment->expose();
            $data['transactions'][] = [
                'type'   => 'shipment',
                'amount' => $shipment->getAmount(),
                'date'   => $shipment->getDate(),
                'id'     => $shipment->getId(),
            ];
        }

        /** @var Cancellation $cancellation */
        foreach ($resource->getCancellations() as $cancellation) {
            $data['cancellations'][] = $cancellation->expose();
            $data['transactions'][]  = [
                'type'   => 'cancellation',
                'amount' => $cancellation->getAmount(),
                'date'   => $cancellation->getDate(),
                'id'     => $cancellation->getId(),
            ];
        }

        foreach ($resource->getMetadata()->expose() as $key => $value) {
            $data['metadata'][] = [
                'key'   => $key,
                'value' => $value,
            ];
        }

        return $data;
    }
}
