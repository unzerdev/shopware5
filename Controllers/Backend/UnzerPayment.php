<?php

declare(strict_types=1);

use Shopware\Components\Routing\Context;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;
use UnzerPayment\Components\Converter\BasketConverter\BasketConverterInterface;
use UnzerPayment\Components\Hydrator\ArrayHydrator\ArrayHydratorInterface;
use UnzerPayment\Services\DocumentHandler\DocumentHandlerServiceInterface;
use UnzerPayment\Services\PaymentIdentification\PaymentIdentificationServiceInterface;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientService;
use UnzerPayment\Subscribers\Model\OrderSubscriber;
use UnzerSDK\Constants\CancelReasonCodes;
use UnzerSDK\Constants\WebhookEvents;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Interfaces\WebhookServiceInterface;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Cancellation;
use UnzerSDK\Resources\TransactionTypes\Charge;
use UnzerSDK\Resources\TransactionTypes\Shipment;
use UnzerSDK\Resources\Webhook;
use UnzerSDK\Unzer;

class Shopware_Controllers_Backend_UnzerPayment extends Shopware_Controllers_Backend_Application
{
    /**
     * {@inheritdoc}
     */
    protected $model = Order::class;

    /**
     * {@inheritdoc}
     */
    protected $alias = 'sOrder';

    private ?Unzer $unzerPaymentClient;

    private UnzerPaymentApiLoggerServiceInterface $logger;

    private DocumentHandlerServiceInterface $documentHandlerService;

    private PaymentIdentificationServiceInterface $paymentIdentificationService;

    private ?Shop $shop;

    /**
     * {@inheritdoc}
     */
    public function preDispatch(): void
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        $this->logger                       = $this->container->get('unzer_payment.services.api_logger');
        $this->documentHandlerService       = $this->container->get('unzer_payment.services.document_handler');
        $this->paymentIdentificationService = $this->container->get('unzer_payment.services.payment_identification_service');
        $modelManager                       = $this->container->get('models');
        $shopId                             = $this->request->get('shopId');
        /** @var UnzerPaymentClientService $unzerPaymentClientService */
        $unzerPaymentClientService = $this->container->get('unzer_payment.services.api_client');

        if ($shopId) {
            $this->shop = $modelManager->find(Shop::class, $shopId);
        } else {
            $this->shop = $modelManager->getRepository(Shop::class)->getActiveDefault();
        }

        if ($this->shop === null) {
            throw new RuntimeException('Could not determine shop context');
        }

        if ($this->request->getActionName() === 'registerWebhooks') {
            return;
        }

        $locale = $this->container->get('locale')->toString();
        // TODO PAYMENT_ID_VS_TRANSACTION_ID_ISSUE
        // In several requests we use an ID to determine the client e. g. 's-pay-123'. Either it's named unzerPaymentId, paymentId or transactionId.
        // This leads to confusion. Correct this.
        // Search for PAYMENT_ID_VS_TRANSACTION_ID_ISSUE to see a related issue.
        $unzerPaymentId           = $this->request->get('unzerPaymentId') ?? $this->request->get('paymentId') ?? $this->request->get('transactionId');
        $this->unzerPaymentClient = $unzerPaymentClientService->getUnzerPaymentClientByPaymentId($unzerPaymentId, $locale);

        if ($this->unzerPaymentClient === null) {
            $this->logger->getPluginLogger()->error('Could not initialize the Unzer Payment client');
        }
    }

    public function paymentDetailsAction(): void
    {
        if (!$this->unzerPaymentClient) {
            return;
        }

        /** @var ArrayHydratorInterface $arrayHydrator */
        $arrayHydrator = $this->container->get('unzer_payment.array_hydrator.payment.lazy');
        $orderId       = $this->Request()->get('orderId');
        $transactionId = $this->Request()->get('transactionId');
        $paymentName   = $this->Request()->get('paymentName');

        try {
            $result                    = $this->unzerPaymentClient->fetchPayment($transactionId);
            $data                      = $arrayHydrator->hydrateArray($result);
            $data['isFinalizeAllowed'] = false;

            if (count($data['shipments']) < 1 && in_array($paymentName, OrderSubscriber::ALLOWED_FINALIZE_METHODS, true)
                && $this->documentHandlerService->isDocumentCreatedByOrderId((int) $orderId)
            ) {
                $data['isFinalizeAllowed'] = true;
            }

            /* Basket V2 since Version 1.1.5 */
            if (!empty($data['basket']['totalValueGross'])) {
                /** @var BasketConverterInterface $basketConverter */
                $basketConverter = $this->container->get('unzer_payment.converter.basket_converter');

                $data['basket'] = $basketConverter->populateDeprecatedVariables((int) $orderId, $data['basket']);
            }

            $this->view->assign([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (UnzerApiException $apiException) {
            $this->view->assign([
                'success' => false,
                'message' => $apiException->getClientMessage(),
            ]);

            $this->logger->logException(sprintf('Error while requesting payment details for order-id [%s]', $orderId), $apiException);
        }
    }

    public function loadPaymentTransactionAction(): void
    {
        if (!$this->unzerPaymentClient) {
            return;
        }

        $orderId = $this->Request()->get('unzerPaymentId');
        // In the case of a reversal or cancellation, the transaction ID from the request also contains the parent ID, separated by a slash.
        // This is necessary because a cancellation has different parents (authorization or charge) and the ID can therefore exist more than once.
        $transactionIds      = explode('/', $this->Request()->get('transactionId'));
        $transactionId       = array_pop($transactionIds);
        $parentTransactionId = count($transactionIds) > 0 ? array_pop($transactionIds) : null;

        $transactionType = $this->Request()->get('transactionType');

        try {
            $response = [
                'success' => false,
                'data'    => 'no valid transaction type found',
            ];

            $payment           = $this->unzerPaymentClient->fetchPaymentByOrderId($orderId);
            $remainingAmount   = null;
            $transactionResult = null;

            switch ($transactionType) {
                case 'authorization':
                    /** @var Authorization $transactionResult */
                    $transactionResult = $payment->getAuthorization();
                    $remainingAmount   = $payment->getAmount()->getRemaining();

                    break;

                case 'charge':
                    /** @var Charge $transactionResult */
                    $transactionResult = $payment->getCharge($transactionId);
                    $remainingAmount   = $transactionResult->getAmount() - $transactionResult->getCancelledAmount();

                    break;

                case 'cancellation':
                    /** @var Cancellation $cancellation */
                    foreach ($payment->getCancellations() as $cancellation) {
                        /** @var Authorization|Charge $parent */
                        $parent = $cancellation->getParentResource();

                        if ($parentTransactionId !== null && $parent->getId() !== $parentTransactionId) {
                            continue;
                        }

                        if ($cancellation->getId() !== $transactionId) {
                            continue;
                        }

                        $transactionResult = $cancellation;

                        break;
                    }

                    break;

                case 'reversal':
                    /** @var Cancellation $reversal */
                    foreach ($payment->getReversals() as $reversal) {
                        if ($reversal->getId() !== $transactionId) {
                            continue;
                        }

                        $transactionResult = $reversal;

                        break;
                    }

                    break;

                case 'refund':
                    /** @var Cancellation $refund */
                    foreach ($payment->getRefunds() as $refund) {
                        if ($refund->getId() !== $transactionId) {
                            continue;
                        }

                        $transactionResult = $refund;

                        break;
                    }

                    break;

                case 'shipment':
                    /** @var Shipment $transactionResult */
                    $transactionResult = $payment->getShipment($transactionId);

                    break;
                default:
                    $this->view->assign([
                        'success' => false,
                        'data'    => 'no valid transaction type found',
                    ]);

                    return;
            }

            if ($transactionResult !== null) {
                $transactionResultId = $transactionResult->getId();

                if ($parentTransactionId) {
                    $transactionResultId = $parentTransactionId . '/' . $transactionResultId;
                }

                $response = [
                    'success' => true,
                    'data'    => [
                        'type'            => $transactionType,
                        'id'              => $transactionResultId,
                        'shortId'         => $transactionResult->getShortId(),
                        'date'            => $transactionResult->getDate(),
                        'amount'          => $transactionResult->getAmount(),
                        'remainingAmount' => $remainingAmount,
                    ],
                ];
            }
        } catch (UnzerApiException $apiException) {
            $response = [
                'success' => false,
                'message' => $apiException->getClientMessage(),
            ];

            $this->logger->logException(sprintf('Error while requesting transaction details for order-id [%s]', $orderId), $apiException);
        }

        $this->view->assign($response);
    }

    public function chargeAction(): void
    {
        if (!$this->unzerPaymentClient) {
            return;
        }

        $paymentId = $this->request->get('paymentId');
        $amount    = floatval($this->request->get('amount'));

        if ($amount === 0.0) {
            return;
        }

        try {
            $result = $this->unzerPaymentClient->chargeAuthorization($paymentId, $amount);

            $this->updateOrderPaymentStatus($result->getPayment());

            $this->view->assign([
                'success' => true,
                'data'    => $result->expose(),
                'message' => $result->getMessage(),
            ]);
        } catch (UnzerApiException $apiException) {
            $this->view->assign([
                'success' => false,
                'message' => $apiException->getClientMessage(),
            ]);

            $this->logger->logException(sprintf('Error while charging payment with id [%s] with an amount of [%s]', $paymentId, $amount), $apiException);
        }
    }

    public function refundAction(): void
    {
        if (!$this->unzerPaymentClient) {
            return;
        }

        $paymentId = $this->request->get('paymentId');
        $amount    = (float) $this->request->get('amount');
        $chargeId  = $this->request->get('chargeId');
        $shopId    = (int) $this->request->get('shopId');

        if ($amount === 0.0) {
            return;
        }

        try {
            if ($this->paymentIdentificationService->chargeCancellationNeedsCancellationObject($paymentId, $shopId)) {
                $cancellation = new Cancellation($amount);
                $cancellation = $this->unzerPaymentClient->cancelChargedPayment($paymentId, $cancellation);
                $payment      = $cancellation->getPayment();
                $expose       = $cancellation->expose();
                $message      = $cancellation->getMessage()->getMerchant();
            } else {
                $charge  = $this->unzerPaymentClient->fetchChargeById($paymentId, $chargeId);
                $result  = $charge->cancel($amount, CancelReasonCodes::REASON_CODE_CANCEL);
                $payment = $result->getPayment();
                $expose  = $result->expose();
                $message = $result->getMessage()->getMerchant();
            }

            $this->updateOrderPaymentStatus($payment);

            $this->view->assign([
                'success' => true,
                'data'    => $expose,
                'message' => $message,
            ]);
        } catch (UnzerApiException $apiException) {
            $this->view->assign([
                'success' => false,
                'message' => $apiException->getClientMessage(),
            ]);

            $this->logger->logException(sprintf('Error while refunding the charge with id [%s] (Payment-Id: [%s]) with an amount of [%s]', $chargeId, $paymentId, $amount), $apiException);
        }
    }

    public function cancelAction(): void
    {
        if (!$this->unzerPaymentClient) {
            return;
        }

        $amount = (float) $this->request->get('amount');

        if ($amount === 0.0) {
            return;
        }

        $paymentId = $this->request->get('paymentId');
        $shopId    = (int) $this->request->get('shopId');

        try {
            if ($this->paymentIdentificationService->chargeCancellationNeedsCancellationObject($paymentId, $shopId)) {
                $cancellation = new Cancellation($amount);
                $cancellation = $this->unzerPaymentClient->cancelAuthorizedPayment($paymentId, $cancellation);
                $payment      = $cancellation->getPayment();
                $expose       = $cancellation->expose();
                $message      = $cancellation->getMessage()->getMerchant();
            } else {
                $charge  = $this->unzerPaymentClient->fetchAuthorization($paymentId);
                $result  = $charge->cancel($amount);
                $payment = $result->getPayment();
                $expose  = $result->expose();
                $message = $result->getMessage()->getMerchant();
            }

            $this->updateOrderPaymentStatus($payment);

            $this->view->assign([
                'success' => true,
                'data'    => $expose,
                'message' => $message,
            ]);
        } catch (UnzerApiException $apiException) {
            $this->view->assign([
                'success' => false,
                'message' => $apiException->getClientMessage(),
            ]);

            $this->logger->logException(sprintf('Error while cancelling the authorization with id [%s] with an amount of [%s]', $paymentId, $amount), $apiException);
        }
    }

    public function finalizeAction(): void
    {
        if (!$this->unzerPaymentClient) {
            return;
        }

        $orderId   = $this->request->get('orderId');
        $paymentId = $this->request->get('paymentId');

        $invoiceDocumentId = $this->documentHandlerService->getDocumentIdByOrderId((int) $orderId);

        if (!$invoiceDocumentId) {
            $this->view->assign([
                'success' => false,
                'message' => 'Could not find any invoice for this order.',
            ]);

            return;
        }

        try {
            $result = $this->unzerPaymentClient->ship($paymentId, (string) $invoiceDocumentId);

            $this->updateOrderPaymentStatus($result->getPayment());

            $this->view->assign([
                'success' => true,
                'data'    => $result->expose(),
                'message' => $result->getMessage(),
            ]);
        } catch (UnzerApiException $apiException) {
            $this->view->assign([
                'success' => false,
                'message' => $apiException->getClientMessage(),
            ]);

            $this->logger->logException(sprintf('Error while sending shipping notification for the payment-id [%s]', $paymentId), $apiException);
        }
    }

    public function registerWebhooksAction(): void
    {
        $locale         = $this->container->get('locale')->toString();
        $keypairClients = $this->container->get('unzer_payment.services.api_client')->getExistingKeypairClients($locale, $this->shop->getId());

        if (empty($keypairClients)) {
            return;
        }

        $success = false;
        $message = '';
        $context = $this->getContext();
        $url     = $this->getShopUrl($context);

        try {
            foreach ($keypairClients as $keypairClient) {
                $this->resetWebhooks($keypairClient, $context);
                $this->registerWebhook($keypairClient, $context);
            }

            $this->logger->getPluginLogger()->alert(sprintf('All webhooks have been successfully registered to the following URL: %s', $url));

            $success = true;
        } catch (UnzerApiException $apiException) {
            $message = $apiException->getMerchantMessage();

            $this->logger->logException(sprintf('Error while registering the webhooks to [%s]', $url), $apiException);
        } catch (RuntimeException $genericException) {
            $message = $genericException->getMessage();

            $this->logger->getPluginLogger()->error(sprintf('Error while registering the webhooks to [%s]: %s', $url, $message));
        }

        $this->view->assign(compact('success', 'message'));
    }

    public function testCredentialsAction(): void
    {
        $locale         = $this->container->get('locale')->toString();
        $keypairClients = $this->container->get('unzer_payment.services.api_client')->getExistingKeypairClients($locale, $this->shop->getId());

        if (empty($keypairClients)) {
            return;
        }

        $success = false;

        try {
            $configService = $this->container->get('unzer_payment.services.config_reader');

            $errorMessages = [];
            foreach ($keypairClients as $keypairType => $keypairClient) {
                $result = $keypairClient->fetchKeypair();

                $publicKeyConfigKey = UnzerPaymentClientService::PUBLIC_CONFIG_KEYS[$keypairType];
                $publicKeyConfig    = (string) $configService->get($publicKeyConfigKey);

                if ($result->getPublicKey() !== $publicKeyConfig) {
                    $message         = sprintf('The given key %s for the config %s is unknown or invalid.', $publicKeyConfig, $publicKeyConfigKey);
                    $errorMessages[] = $message;
                    $this->logger->getPluginLogger()->error(sprintf('API Credentials test failed: %s', $message));
                }
            }

            $success = empty($errorMessages);
            $message = implode(' ', $errorMessages);

            if ($success) {
                $this->logger->getPluginLogger()->alert('API Credentials test succeeded.');
            }
        } catch (UnzerApiException $apiException) {
            $message = $apiException->getMerchantMessage();

            $this->logger->getPluginLogger()->error(sprintf('API Credentials test failed: %s', $message));
        } catch (RuntimeException $genericException) {
            $message = $genericException->getMessage();

            $this->logger->getPluginLogger()->error(sprintf('API Credentials test failed: %s', $message));
        }

        $this->view->assign(compact('success', 'message'));
    }

    protected function getShopUrl(Context $context): string
    {
        return $this->container->get('router')->assemble([
            'controller' => 'UnzerPayment',
            'action'     => 'executeWebhook',
            'module'     => 'frontend',
        ], $context);
    }

    private function resetWebhooks(WebhookServiceInterface $client, Context $context): void
    {
        $shopHost         = $this->get('router')->assemble([], $context);
        $existingWebhooks = $client->fetchAllWebhooks();

        if ($shopHost !== null && count($existingWebhooks) > 0) {
            foreach ($existingWebhooks as $webhook) {
                /** @var Webhook $webhook */
                if (strpos($webhook->getUrl(), $shopHost) === 0) {
                    $client->deleteWebhook($webhook);
                }
            }
        }
    }

    /**
     * @throws UnzerApiException
     */
    private function registerWebhook(WebhookServiceInterface $client, Context $context): void
    {
        $client->createWebhook($this->getShopUrl($context), WebhookEvents::ALL);
    }

    private function updateOrderPaymentStatus(Payment $payment = null): void
    {
        if (!$payment || !((bool) $this->container->get('unzer_payment.services.config_reader')->get('automatic_payment_status'))) {
            return;
        }

        $this->container->get('unzer_payment.services.order_status')->updatePaymentStatusByPayment($payment);
    }

    /**
     * @throws Exception
     */
    private function getContext(): Context
    {
        $context = Context::createFromShop($this->shop->getMain() ?? $this->shop, $this->get('config'));

        if ($this->shop->getMain() !== null) {
            $context->setBaseUrl($context->getBaseUrl() . $this->shop->getBaseUrl());
            $context->setShopId($this->shop->getId());
        }

        return $context;
    }
}
