<?php

use HeidelPayment\Services\HeidelpayApiLoggerServiceInterface;
use HeidelPayment\Services\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use heidelpayPHP\Constants\CancelReasonCodes;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\Payment;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Order\Document\Document;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;

class Shopware_Controllers_Backend_Heidelpay extends Shopware_Controllers_Backend_Application implements CSRFWhitelistAware
{
    const WHITELISTED_CSRF_ACTIONS = [
        'registerWebhooks',
        'testCredentials',
    ];

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

    /** @var HeidelpayApiLoggerServiceInterface */
    private $logger;

    /**
     * {@inheritdoc}
     */
    public function preDispatch()
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        $this->logger = $this->container->get('heidel_payment.services.api_logger');
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

        try {
            $this->heidelpayClient = $this->getHeidelpayClient();
        } catch (RuntimeException $ex) {
            $this->view->assign([
                'success' => false,
                'message' => $ex->getMessage(),
            ]);

            $this->logger->getPluginLogger()->error(sprintf('Could not initialize the Heidelpay client: %s', $ex->getMessage()));
        }
    }

    public function paymentDetailsAction()
    {
        if (!$this->heidelpayClient) {
            return;
        }

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

            $this->logger->logException(sprintf('Error while requesting payment details for order-id [%s]', $transactionId), $apiException);
        }
    }

    public function chargeAction()
    {
        if (!$this->heidelpayClient) {
            return;
        }

        $paymentId = $this->request->get('paymentId');
        $amount    = $this->request->get('amount');

        try {
            $result = $this->heidelpayClient->chargeAuthorization($paymentId, $amount);

            $this->updateOrderPaymentStatus($result->getPayment());

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

            $this->logger->logException(sprintf('Error while charging payment with id [%s] with an amount of [%s]', $paymentId, $amount), $apiException);
        }
    }

    public function refundAction()
    {
        if (!$this->heidelpayClient) {
            return;
        }

        $paymentId = $this->request->get('paymentId');
        $amount    = $this->request->get('amount');
        $chargeId  = $this->request->get('chargeId');

        try {
            $charge = $this->heidelpayClient->fetchChargeById($paymentId, $chargeId);
            $result = $charge->cancel($amount, CancelReasonCodes::REASON_CODE_CANCEL);

            $this->updateOrderPaymentStatus($result->getPayment());

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

            $this->logger->logException(sprintf('Error while refunding the charge with id [%s] (Payment-Id: [%s]) with an amount of [%s]', $chargeId, $paymentId, $amount), $apiException);
        }
    }

    public function finalizeAction()
    {
        if (!$this->heidelpayClient) {
            return;
        }

        $paymentId = $this->request->get('paymentId');
        $orderId   = $this->request->get('orderId');

        /** @var null|Document $invoiceDocument */
        $invoiceDocument = $this->container->get('models')->getRepository(Document::class)->findOneBy([
            'orderId' => $orderId,
            'typeId'  => ViewBehaviorHandlerInterface::DOCUMENT_TYPE_INVOICE,
        ]);

        if (!$invoiceDocument) {
            $this->view->assign([
                'success' => false,
                'message' => 'Could not find any invoice for this order.',
            ]);

            return;
        }

        try {
            $result = $this->heidelpayClient->ship($paymentId, $invoiceDocument->getDocumentId());

            $this->updateOrderPaymentStatus($result->getPayment());

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

            $this->logger->logException(sprintf('Error while sending shipping notification for the payment-id [%s]', $paymentId), $apiException);
        }
    }

    public function registerWebhooksAction()
    {
        if (!$this->heidelpayClient) {
            return;
        }

        $success = false;
        $message = '';
        $url     = $this->container->get('router')->assemble([
            'controller' => 'heidelpay',
            'action'     => 'executeWebhook',
            'module'     => 'frontend',
        ]);

        try {
            $this->heidelpayClient->deleteAllWebhooks();
            $this->heidelpayClient->createWebhook($url, 'all');

            $this->logger->getPluginLogger()->alert(sprintf('All webhooks have been successfully registered to the following URL: %s', $url));

            $success = true;
        } catch (HeidelpayApiException $apiException) {
            $message = $apiException->getMerchantMessage();

            $this->logger->logException(sprintf('Error while registering the webhooks to [%s]', $url), $apiException);
        } catch (RuntimeException $genericException) {
            $message = $genericException->getMessage();

            $this->logger->getPluginLogger()->error(sprintf('Error while registering the webhooks to [%s]: %s', $url, $message));
        }

        $this->view->assign([
            'success' => $success,
            'message' => $message,
        ]);
    }

    public function testCredentialsAction()
    {
        if (!$this->heidelpayClient) {
            return;
        }

        $success = false;
        $message = '';

        try {
            $configService = $this->container->get('heidel_payment.services.config_reader');
            $publicKey     = (string) $configService->get('public_key');
            $result        = $this->heidelpayClient->fetchKeypair();

            if ($result->getPublicKey() !== $publicKey) {
                $message = sprintf('The given key %s is unknown or invalid.', $publicKey);

                $this->logger->getPluginLogger()->error(sprintf('API Credentials test failed: The given key %s is unknown or invalid.', $publicKey));
            } else {
                $success = true;

                $this->logger->getPluginLogger()->alert('API Credentials test succeeded.');
            }
        } catch (HeidelpayApiException $apiException) {
            $message = $apiException->getMerchantMessage();

            $this->logger->getPluginLogger()->error(sprintf('API Credentials test failed: %s', $message));
        } catch (RuntimeException $genericException) {
            $message = $genericException->getMessage();

            $this->logger->getPluginLogger()->error(sprintf('API Credentials test failed: %s', $message));
        }

        $this->view->assign([
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getWhitelistedCSRFActions(): array
    {
        return self::WHITELISTED_CSRF_ACTIONS;
    }

    private function getHeidelpayClient(): Heidelpay
    {
        $locale        = $this->container->get('locale')->toString();
        $configService = $this->container->get('heidel_payment.services.config_reader');

        $privateKey = (string) $configService->get('private_key');

        $heidelpayClient = new Heidelpay($privateKey, $locale);
        $heidelpayClient->setDebugMode($configService->get('transaction_mode') === 'test');
        $heidelpayClient->setDebugHandler($this->logger);

        return $heidelpayClient;
    }

    private function updateOrderPaymentStatus(Payment $payment = null)
    {
        if (!$payment || !((bool) $this->container->get('heidel_payment.services.config_reader')->get('automatic_payment_status'))) {
            return;
        }

        $this->container->get('heidel_payment.services.order_status')->updatePaymentStatusByPayment($payment);
    }
}
