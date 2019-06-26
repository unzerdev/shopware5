<?php

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Resources\TransactionTypes\Shipment;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;

class Shopware_Controllers_Backend_Heidelpay extends Shopware_Controllers_Backend_Application
{
    /**
     * {@inheritdoc}
     */
    protected $model = Order::class;

    /**
     * {@inheritdoc}
     */
    protected $alias = 'sOrder';

    /** @var Heidelpay */
    private $heidelpayClient;

    /**
     * {@inheritdoc}
     */
    public function preDispatch()
    {
        $modelManager = $this->container->get('models');
        $shopId       = $this->request->get('shopId');

        /** @var Shop $shop */
        $shop = null;

        if (!$shopId) {
            $shop = $modelManager->getRepository(Shop::class)->getActiveDefault();
        } else {
            $shop = $this->container->get('models')->find(Shop::class, $shopId);
        }

        if ($shop === null) {
            throw new RuntimeException('Could not determine shop context');
        }

        $configReader = $this->container->get('shopware.plugin.cached_config_reader');

        $pluginConfig = $configReader->getByPluginName(
            $this->container->getParameter('heidel_payment.plugin_name'),
            $shop
        );

        $this->heidelpayClient = new Heidelpay($pluginConfig['private_key']); //TODO check if we can get the backend language

        $this->Front()->Plugins()->Json()->setRenderer();
    }

    public function paymentDetailsAction(): void
    {
        $transactionId = $this->Request()->get('transactionId');

        try {
            $result        = $this->heidelpayClient->fetchPaymentByOrderId($transactionId);
            $authorization = $result->getAuthorization();
            $data          = array_merge($result->expose(), [
                'state' => [
                    'name' => $result->getStateName(),
                    'id'   => $result->getState(),
                ],
                'currency'      => $result->getCurrency(),
                'authorization' => $authorization ? $authorization->expose() : null,
                'basket'        => $result->getBasket() ? $result->getBasket()->expose() : null,
                'customer'      => $result->getCustomer() ? $result->getCustomer()->expose() : null,
                'metadata'      => [],
                'type'          => $result->getPaymentType() ? $result->getPaymentType()->expose() : null,
                'amount'        => $result->getAmount() ? $result->getAmount()->expose() : null,
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
            foreach ($result->getCharges() as $charge) {
                $data['charges'][]      = $charge->expose();
                $data['transactions'][] = [
                    'type'   => 'charge',
                    'amount' => $charge->getAmount(),
                    'date'   => $charge->getDate(),
                    'id'     => $charge->getId(),
                ];
            }

            /** @var Shipment $shipment */
            foreach ($result->getShipments() as $shipment) {
                $data['shipments'][]    = $shipment->expose();
                $data['transactions'][] = [
                    'type'   => 'shipment',
                    'amount' => $shipment->getAmount(),
                    'date'   => $shipment->getDate(),
                    'id'     => $shipment->getId(),
                ];
            }

            /** @var Cancellation $cancellation */
            foreach ($result->getCancellations() as $cancellation) {
                $data['cancellations'][] = $cancellation->expose();
                $data['transactions'][]  = [
                    'type'   => 'cancellation',
                    'amount' => $cancellation->getAmount(),
                    'date'   => $cancellation->getDate(),
                    'id'     => $cancellation->getId(),
                ];
            }

            foreach ($result->getMetadata()->expose() as $key => $value) {
                $data['metadata'][] = [
                    'key'   => $key,
                    'value' => $value,
                ];
            }
            $this->view->assign([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (HeidelpayApiException $apiException) {
            $this->view->assign([
                'success' => false,
                'message' => $apiException->getClientMessage(),
            ]);
        }
    }

    public function chargeAction(): void
    {
        $paymentId = $this->request->get('paymentId');
        $amount    = $this->request->get('amount');

        try {
            $result = $this->heidelpayClient->chargeAuthorization($paymentId, $amount);

            $this->view->assign([
                'success' => true,
                'data'    => $result->expose(),
                'message' => $result->getMessage(),
            ]);
        } catch (HeidelpayApiException $apiException) {
            $this->view->assign([
                'success' => false,
                'message' => $apiException->getClientMessage(),
            ]);
        }
    }

    public function refundAction()
    {
        $paymentId = $this->request->get('paymentId');
        $amount    = $this->request->get('amount');
        $chargeId  = $this->request->get('chargeId');

        try {
            $result = $this->heidelpayClient->cancelChargeById($paymentId, $chargeId, $amount);

            $this->view->assign([
                'success' => true,
                'data'    => $result->expose(),
                'message' => $result->getMessage(),
            ]);
        } catch (HeidelpayApiException $apiException) {
            $this->view->assign([
                'success' => false,
                'message' => $apiException->getClientMessage(),
            ]);
        }
    }
}
