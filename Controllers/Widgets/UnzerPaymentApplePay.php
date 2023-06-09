<?php

declare(strict_types=1);

use League\Flysystem\FilesystemInterface;
use Shopware\Components\Plugin\Configuration\ReaderInterface;
use Shopware\Models\Shop\DetachedShop;
use UnzerPayment\Components\ApplePay\CertificateManager;
use UnzerPayment\Components\BookingMode;
use UnzerPayment\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientService;
use UnzerSDK\Adapter\ApplepayAdapter;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\ExternalResources\ApplepaySession;

class Shopware_Controllers_Widgets_UnzerPaymentApplePay extends AbstractUnzerPaymentController
{
    use CanAuthorize;
    use CanCharge;
    private const MERCHANT_VALIDATION_URL_PARAM = 'merchantValidationUrl';

    /** @var bool */
    protected $isAsync = true;

    /** @var bool */
    protected $isRedirectPayment = true;

    /** @var CertificateManager */
    private $certificateManager;

    /** @var ReaderInterface */
    private $configReader;

    /** @var string $pluginName */
    private $pluginName;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var UnzerPaymentClientService */
    private $unzerPaymentClientService;

    /** @var UnzerPaymentApiLoggerServiceInterface */
    private $logger;

    public function preDispatch(): void
    {
        parent::preDispatch();

        $this->certificateManager        = $this->container->get('unzer_payment.components.apple_pay.certificate_manager');
        $this->configReader              = $this->container->get(Shopware\Components\Plugin\Configuration\CachedReader::class);
        $this->pluginName                = $this->container->getParameter('unzer_payment.plugin_name');
        $this->filesystem                = $this->container->get('shopware.filesystem.private');
        $this->unzerPaymentClientService = $this->container->get('unzer_payment.services.api_client');
        $this->logger                    = $this->container->get('unzer_payment.services.api_logger');
    }

    public function createPaymentAction(): void
    {
        parent::pay();

        $resourceId = $this->request->get('unzerResourceId', '');

        dd(Shopware()->Session()->get('sOrderVariables'));

        if (!empty($resourceId)) {
            $this->paymentType = $this->unzerClient->fetchPaymentType($resourceId);
        }

        $this->handleNormalPayment();
    }

    public function authorizePaymentAction(): void
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        /** @var DetachedShop $shop */
        $shop   = $this->container->get('shop');
        $locale = $shop->getLocale();
        $client = $this->unzerPaymentClientService->getUnzerPaymentClient($locale !== null ? $locale->getLocale() : 'en-GB', $shop->getId());
        $typeId = $this->request->get('id');

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

            throw $e;
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

        $shopId = $shop->getId();
        $config = $this->configReader->getByPluginName($this->pluginName, $shopId);

        $applePaySession = new ApplepaySession(
            $config['apple_pay_merchant_id'],
            $displayName,
            $this->request->getHost()
        );
        $appleAdapter = new ApplepayAdapter();

        $certificatePath = $this->certificateManager->getMerchantIdentificationCertificatePath($shopId);
        $keyPath         = $this->certificateManager->getMerchantIdentificationKeyPath($shopId);

        if (!$this->filesystem->has($certificatePath) || !$this->filesystem->has($keyPath)) {
            // Try for fallback configuration
            $certificatePath = $this->certificateManager->getMerchantIdentificationCertificatePath(null);
            $keyPath         = $this->certificateManager->getMerchantIdentificationKeyPath(null);

            if (!$this->filesystem->has($certificatePath) || !$this->filesystem->has($keyPath)) {
                throw new Exception('Merchant Identification missing'); // todo: Explicit exception
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
        /** @var DetachedShop $shop */
        $shop = $this->container->get('shop');

        $bookingMode = $this->container->get('unzer_payment.services.config_reader')->get('apple_pay_bookingmode', $shop->getId());

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
            $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
        } finally {
            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }
}
