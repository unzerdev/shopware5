<?php

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
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
        $arrayHydrator = $this->container->get('heidel_payment.array_hydrator.payment');

        try {
            $result = $this->heidelpayClient->fetchPaymentByOrderId($transactionId);
            $data   = $arrayHydrator->hydrateArray($result);

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

    public function refundAction(): void
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

    public function finalizeAction(): void
    {
        $paymentId = $this->request->get('paymentId');

        try {
            $result = $this->heidelpayClient->ship($paymentId);

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
