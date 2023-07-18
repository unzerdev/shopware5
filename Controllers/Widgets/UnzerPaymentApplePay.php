<?php

declare(strict_types=1);

use League\Flysystem\FilesystemInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\DetachedShop;
use Shopware\Models\Shop\Shop;
use UnzerPayment\Components\ApplePay\CertificateManager;
use UnzerPayment\Components\BookingMode;
use UnzerPayment\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientService;
use UnzerPayment\Subscribers\Frontend\Checkout;
use UnzerSDK\Adapter\ApplepayAdapter;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\ExternalResources\ApplepaySession;
use UnzerSDK\Unzer;

class Shopware_Controllers_Widgets_UnzerPaymentApplePay extends AbstractUnzerPaymentController
{
    use CanAuthorize;
    use CanCharge;
    private const MERCHANT_VALIDATION_URL_PARAM = 'merchantValidationUrl';

    /** @var bool */
    protected $isAsync = true;

    /** @var CertificateManager */
    private $certificateManager;

    /** @var ConfigReaderServiceInterface */
    private $configReader;

    /** @var string */
    private $pluginName;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var UnzerPaymentClientService */
    private $unzerPaymentClientService;

    /** @var UnzerPaymentApiLoggerServiceInterface */
    private $logger;

    /** @var Shop */
    private $shop;

    /** @var ModelManager */
    private $modelManager;

    public function preDispatch(): void
    {
        parent::preDispatch();

        $this->certificateManager        = $this->container->get('unzer_payment.components.apple_pay.certificate_manager');
        $this->configReader              = $this->container->get('unzer_payment.services.config_reader');
        $this->pluginName                = $this->container->getParameter('unzer_payment.plugin_name');
        $this->filesystem                = $this->container->get('shopware.filesystem.private');
        $this->unzerPaymentClientService = $this->container->get('unzer_payment.services.api_client');
        $this->logger                    = $this->container->get('unzer_payment.services.api_logger');
        $this->shop                      = $this->container->get('shop');
        $this->modelManager              = $this->container->get('models');
    }

    public function createPaymentAction(): void
    {
        parent::pay();

        $resourceId = $this->session->get(Checkout::UNZER_RESOURCE_ID);
        $client     = $this->getUnzerPaymentClient();

        if (empty($resourceId)) {
            throw new RuntimeException('Cannot complete payment without resource id.');
        }

        $this->paymentType = $client->fetchPaymentType($resourceId);
        $this->handleNormalPayment();
    }

    public function authorizePaymentAction(): void
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        $client   = $this->getUnzerPaymentClient();
        $typeId   = $this->request->get('id');
        $response = ['transactionStatus' => 'error'];

        try {
            // Charge/Authorize is done in payment handler, return pending to satisfy Apple Pay widget
            $paymentType                   = $client->fetchPaymentType($typeId);
            $response['transactionStatus'] = 'pending';
        } catch (UnzerApiException $e) {
            $this->View()->assign([
                'clientMessage'   => $e->getClientMessage(),
                'merchantMessage' => $e->getMerchantMessage(),
            ]);

            return;
        } catch (Exception $e) {
            $this->logger->getPluginLogger()->error('Error in Apple Pay authorization call', ['exception' => $e]);

            return;
        }

        $this->View()->assign($response);
    }

    public function validateMerchantAction(): void
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        /** @var DetachedShop $shop */
        $shop = $this->container->get('shop');

        $displayName = $shop->getName();

        if (!is_string($displayName)) {
            $displayName = '';
        }

        $shopId     = $shop->getId();
        $merchantId = $this->configReader->get('apple_pay_merchant_id', $shopId);

        $applePaySession = new ApplepaySession(
            $merchantId,
            $displayName,
            $this->request->getHost()
        );
        $appleAdapter = new ApplepayAdapter();

        $certificatePath = $this->certificateManager->getMerchantIdentificationCertificatePath($shopId);
        $keyPath         = $this->certificateManager->getMerchantIdentificationKeyPath($shopId);

        if (!$this->filesystem->has($certificatePath) || !$this->filesystem->has($keyPath)) {
            // Try for fallback configuration
            $mainShop   = $this->modelManager->getRepository(Shop::class)->getActiveDefault();
            $mainShopId = $mainShop->getId();

            $certificatePath = $this->certificateManager->getMerchantIdentificationCertificatePath($mainShopId);
            $keyPath         = $this->certificateManager->getMerchantIdentificationKeyPath($mainShopId);

            if (!$this->filesystem->has($certificatePath) || !$this->filesystem->has($keyPath)) {
                throw new RuntimeException('Merchant Identification missing');
            }
        }

        // ApplepayAdapter requires certificate as local files
        $certificateTempPath = tempnam(sys_get_temp_dir(), 'UnzerPayment');
        $keyTempPath         = tempnam(sys_get_temp_dir(), 'UnzerPayment');

        if (!$certificateTempPath || !$keyTempPath) {
            throw new RuntimeException('Error on temporary file creation');
        }

        file_put_contents($certificateTempPath, $this->filesystem->read($certificatePath));
        file_put_contents($keyTempPath, $this->filesystem->read($keyPath));

        try {
            $appleAdapter->init($certificateTempPath, $keyTempPath);

            $merchantValidationUrl = urldecode($this->request->get(self::MERCHANT_VALIDATION_URL_PARAM));

            try {
                $validationResponse = $appleAdapter->validateApplePayMerchant(
                    $merchantValidationUrl,
                    $applePaySession
                );

                $this->View()->assign('validationResponse', $validationResponse);
            } catch (Exception $e) {
                $this->logger->getPluginLogger()->error('Error in Apple Pay merchant validation', ['exception' => $e]);

                throw $e;
            }
        } finally {
            unlink($keyTempPath);
            unlink($certificateTempPath);
        }
    }

    private function handleNormalPayment(): void
    {
        $bookingMode = $this->configReader->get(
            'apple_pay_bookingmode',
            $this->shop->getId()
        );

        $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');

        try {
            if ($bookingMode === BookingMode::CHARGE) {
                $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());
            } else {
                $redirectUrl = $this->authorize($this->paymentDataStruct->getReturnUrl());
            }
        } catch (UnzerApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating apple pay payment', $apiException);
            $redirectUrl = $this->getUnzerPaymentErrorUrl($apiException->getClientMessage());
        } catch (RuntimeException $runtimeException) {
            $this->getApiLogger()->getPluginLogger()->error('Error while fetching payment', ['message' => $runtimeException->getMessage(), 'trace' => $runtimeException->getTrace()]);
        } finally {
            $this->session->set(Checkout::UNZER_RESOURCE_ID, null);

            $this->redirect($redirectUrl);
        }
    }

    private function getUnzerPaymentClient(): ?Unzer
    {
        $locale = $this->shop->getLocale();

        return $this->unzerPaymentClientService->getUnzerPaymentClient(
            $locale !== null ? $locale->getLocale() : 'en-GB',
            $this->shop->getId()
        );
    }
}
