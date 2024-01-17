<?php

declare(strict_types=1);

use League\Flysystem\FilesystemInterface;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Shop;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use UnzerPayment\Components\ApplePay\CertificateManager;
use UnzerPayment\Components\Resource\ApplePayCertificate;
use UnzerPayment\Components\Resource\ApplePayPrivateKey;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientService;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Unzer;

class Shopware_Controllers_Backend_ApplePayCertificateManager extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    public const INHERIT_PAYMENT_CONFIGURATION_PARAMETER      = 'inheritPaymentProcessingCertificates';
    public const INHERIT_MERCHANT_CONFIGURATION_PARAMETER     = 'inheritMerchantCertificates';
    public const PAYMENT_PROCESSING_CERTIFICATE_PARAMETER     = 'paymentProcessingCertificate';
    public const PAYMENT_PROCESSING_CERTIFICATE_KEY_PARAMETER = 'paymentProcessingCertificateKey';
    public const MERCHANT_CERTIFICATE_PARAMETER               = 'merchantCertificate';
    public const MERCHANT_CERTIFICATE_KEY_PARAMETER           = 'merchantCertificateKey';

    /** @var Shop */
    private $shop;

    /** @var ModelManager */
    private $modelManager;

    /** @var Unzer */
    private $unzerPaymentClient;

    /** @var UnzerPaymentApiLoggerServiceInterface */
    private $logger;

    /** @var CertificateManager */
    private $certificateManager;

    /** @var FilesystemInterface */
    private $filesystem;

    public function preDispatch(): void
    {
        $this->get('template')->addTemplateDir(__DIR__ . '/../../Resources/views/');

        $this->modelManager = $this->container->get('models');
        $shopId             = $this->request->get('shopId');
        /** @var UnzerPaymentClientService $unzerPaymentClientService */
        $unzerPaymentClientService = $this->container->get('unzer_payment.services.api_client');
        $this->logger              = $this->container->get('unzer_payment.services.api_logger');
        $this->certificateManager  = $this->container->get('unzer_payment.components.apple_pay.certificate_manager');
        $this->filesystem          = $this->container->get('shopware.filesystem.private');

        if ($shopId) {
            $this->shop = $this->modelManager->find(Shop::class, $shopId);
        } else {
            $this->shop = $this->modelManager->getRepository(Shop::class)->getActiveDefault();
        }

        if ($this->shop === null) {
            throw new RuntimeException('Could not determine shop context');
        }

        $locale                   = $this->container->get('locale')->toString();
        $this->unzerPaymentClient = $unzerPaymentClientService->getGeneralUnzerPaymentClient($locale, $shopId !== null ? (int) $shopId : null);

        if ($this->unzerPaymentClient === null) {
            $this->logger->getPluginLogger()->error('Could not initialize the Unzer Payment client');
        }
    }

    public function postDispatch(): void
    {
        $csrfToken = $this->container->get('backendsession')->offsetGet('X-CSRF-Token');

        $this->View()->assign([
            'csrfToken'        => $csrfToken,
            'shopId'           => $this->shop->getId(),
            'isDefaultShop'    => $this->shop->getDefault(),
            'shops'            => $this->modelManager->getRepository(Shop::class)->findAll(),
            'certificateCheck' => $this->checkCertificateValidity(),
        ]);
    }

    public function indexAction(): void
    {
        if ($this->request->get('viewData')) {
            $this->View()->assign($this->request->get('viewData'));
        }
    }

    public function updateCertificatesAction(): void
    {
        $fileBag = $this->request->files;

        try {
            $merchantIdCertificatePath = $this->certificateManager->getMerchantIdentificationCertificatePathForUpdate($this->shop->getId());
            $merchantIdKeyPath         = $this->certificateManager->getMerchantIdentificationKeyPathForUpdate($this->shop->getId());

            if ($this->isCombinedCertificateUpdate($fileBag, self::PAYMENT_PROCESSING_CERTIFICATE_PARAMETER, self::PAYMENT_PROCESSING_CERTIFICATE_KEY_PARAMETER)) {
                $updateSuccessful = $this->updatePaymentProcessingCertificate($fileBag);

                if ($updateSuccessful !== true) {
                    return;
                }
            } elseif ($this->request->has(self::INHERIT_PAYMENT_CONFIGURATION_PARAMETER)) {
                $this->resetPaymentCertificateInheritance();
            } elseif ($this->isPartialCertificateUpdate($fileBag, self::PAYMENT_PROCESSING_CERTIFICATE_PARAMETER, self::PAYMENT_PROCESSING_CERTIFICATE_KEY_PARAMETER)) {
                $this->logger->getPluginLogger()->info('Payment Processing certificate or key missing');

                $this->forwardToIndex([
                    'paymentProcessingCertificatePartialUpdate' => true,
                ]);

                return;
            }

            if ($this->isCombinedCertificateUpdate($fileBag, self::MERCHANT_CERTIFICATE_PARAMETER, self::MERCHANT_CERTIFICATE_KEY_PARAMETER)) {
                $updateSuccessful = $this->updateMerchantIdentificationCertificate($fileBag, $merchantIdCertificatePath, $merchantIdKeyPath);

                if ($updateSuccessful !== true) {
                    return;
                }
            } elseif ($this->request->has(self::INHERIT_MERCHANT_CONFIGURATION_PARAMETER)) {
                $this->resetMerchantIdentificationInheritance($merchantIdCertificatePath, $merchantIdKeyPath);
            } elseif ($this->isPartialCertificateUpdate($fileBag, self::MERCHANT_CERTIFICATE_PARAMETER, self::MERCHANT_CERTIFICATE_KEY_PARAMETER)) {
                $this->logger->getPluginLogger()->info('Merchant certificate or key missing');

                $this->forwardToIndex([
                    'merchantCertificatePartialUpdate' => true,
                ]);

                return;
            }
        } catch (UnzerApiException $e) {
            $this->forwardToIndex([
                'errorMessage' => $e->getMerchantMessage(),
            ]);

            return;
        }

        $this->forwardToIndex([
            'paymentProcessingCertificateUpdated' => true,
            'merchantCertificateUpdated'          => true,
        ]);
    }

    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }

    private function updatePaymentProcessingCertificate(FileBag $fileBag): bool
    {
        /** @var UploadedFile $certificateFile */
        $certificateFile    = $fileBag->get(self::PAYMENT_PROCESSING_CERTIFICATE_PARAMETER);
        $certificateContent = file_get_contents($certificateFile->getRealPath());

        if (extension_loaded('openssl') && !openssl_x509_parse($certificateContent)) {
            $this->logger->getPluginLogger()->info('Invalid Payment Processing certificate given');

            $this->forwardToIndex([
                'paymentProcessingCertificateInvalid' => true,
            ]);

            return false;
        }

        $keyFile    = $fileBag->get(self::PAYMENT_PROCESSING_CERTIFICATE_KEY_PARAMETER);
        $keyContent = file_get_contents($keyFile->getRealPath());

        $privateKeyResource = new ApplePayPrivateKey();
        $privateKeyResource->setCertificate($keyContent);
        $this->unzerPaymentClient->getResourceService()->createResource($privateKeyResource->setParentResource($this->unzerPaymentClient));
        $privateKeyId = $privateKeyResource->getId();

        $certificateResource = new ApplePayCertificate();
        $certificateResource->setCertificate($certificateContent);
        $certificateResource->setPrivateKey($privateKeyId);
        $this->unzerPaymentClient->getResourceService()->createResource($certificateResource->setParentResource($this->unzerPaymentClient));
        $this->certificateManager->setPaymentProcessingCertificateId($certificateResource->getId(), $this->shop->getId());

        return true;
    }

    private function updateMerchantIdentificationCertificate(
        FileBag $fileBag,
        string $merchantIdCertificatePath,
        string $merchantIdKeyPath
    ): bool {
        /** @var UploadedFile $certificateFile */
        $certificateFile    = $fileBag->get(self::MERCHANT_CERTIFICATE_PARAMETER);
        $certificateContent = file_get_contents($certificateFile->getRealPath());

        if (extension_loaded('openssl') && !openssl_x509_parse($certificateContent)) {
            $this->logger->getPluginLogger()->info('Invalid Merchant Identification certificate given');

            $this->forwardToIndex([
                'merchantCertificateInvalid' => true,
            ]);

            return false;
        }

        /** @var UploadedFile $certificateFile */
        $certificateKeyFile = $fileBag->get(self::MERCHANT_CERTIFICATE_KEY_PARAMETER);
        $keyContent         = file_get_contents($certificateKeyFile->getRealPath());

        $this->filesystem->put($merchantIdCertificatePath, $certificateContent);
        $this->filesystem->put($merchantIdKeyPath, $keyContent);

        $this->certificateManager->setMerchantCertificateId((string) $this->shop->getId(), $this->shop->getId());
        $this->logger->getPluginLogger()->debug(sprintf('Merchant Identification certificate for sales channel %s updated', $this->shop->getId()));

        return true;
    }

    private function resetPaymentCertificateInheritance(): void
    {
        $this->certificateManager->setPaymentProcessingCertificateId(null, $this->shop->getId());
    }

    private function resetMerchantIdentificationInheritance(string $merchantIdCertificatePath, string $merchantIdKeyPath): void
    {
        $this->certificateManager->setMerchantCertificateId(null, $this->shop->getId());

        if ($this->filesystem->has($merchantIdCertificatePath)) {
            $this->filesystem->delete($merchantIdCertificatePath);
        }

        if ($this->filesystem->has($merchantIdKeyPath)) {
            $this->filesystem->delete($merchantIdKeyPath);
        }
    }

    private function isPartialCertificateUpdate(FileBag $files, string $certificateIndex, string $keyIndex): bool
    {
        return ($files->has($certificateIndex) && !$files->has($keyIndex))
            || (!$files->has($certificateIndex) && $files->has($keyIndex));
    }

    private function isCombinedCertificateUpdate(FileBag $files, string $certificateIndex, string $keyIndex): bool
    {
        return $files->has($certificateIndex) && $files->has($keyIndex);
    }

    private function forwardToIndex(array $viewData): void
    {
        $this->forward('index', 'ApplePayCertificateManager', 'backend', [
            'viewData' => $viewData,
        ]);
    }

    private function checkCertificateValidity(): array
    {
        $shopId                           = $this->shop->getId();
        $mainShop                         = $this->modelManager->getRepository(Shop::class)->getActiveDefault();
        $mainShopId                       = $mainShop->getId();
        $paymentProcessingValid           = false;
        $paymentProcessingInherited       = false;
        $merchantIdentificationValid      = false;
        $merchantIdentificationInherited  = false;
        $merchantIdentificationValidUntil = null;

        if ($this->filesystem->has($this->certificateManager->getMerchantIdentificationCertificatePath($shopId)) &&
            $this->filesystem->has($this->certificateManager->getMerchantIdentificationKeyPath($shopId))) {
            $merchantIdentificationValid = true;

            if (extension_loaded('openssl')) {
                $certificateData = openssl_x509_parse((string) $this->filesystem->read($this->certificateManager->getMerchantIdentificationCertificatePath($shopId)));

                if (is_array($certificateData) && array_key_exists('validTo_time_t', $certificateData)) {
                    $merchantIdentificationValidUntil = \DateTimeImmutable::createFromFormat('U', (string) $certificateData['validTo_time_t']) ?: null;
                }
            }
        } elseif ($mainShopId !== null && $this->filesystem->has($this->certificateManager->getMerchantIdentificationCertificatePath($mainShopId)) &&
            $this->filesystem->has($this->certificateManager->getMerchantIdentificationKeyPath($mainShopId))) {
            $merchantIdentificationValid     = true;
            $merchantIdentificationInherited = true;

            if (extension_loaded('openssl')) {
                $certificateData = openssl_x509_parse((string) $this->filesystem->read($this->certificateManager->getMerchantIdentificationCertificatePath($mainShopId)));

                if (is_array($certificateData) && array_key_exists('validTo_time_t', $certificateData)) {
                    $merchantIdentificationValidUntil = \DateTimeImmutable::createFromFormat('U', (string) $certificateData['validTo_time_t']) ?: null;
                }
            }
        }

        if ($this->certificateManager->getPaymentProcessingCertificateId($shopId) !== null) {
            $paymentProcessingValid = true;
        } elseif ($mainShopId !== null && $this->certificateManager->getPaymentProcessingCertificateId($mainShopId) !== null) {
            $paymentProcessingValid     = true;
            $paymentProcessingInherited = true;
        }

        return [
            'paymentProcessingValid'           => $paymentProcessingValid,
            'paymentProcessingInherited'       => $paymentProcessingInherited,
            'merchantIdentificationValid'      => $merchantIdentificationValid,
            'merchantIdentificationInherited'  => $merchantIdentificationInherited,
            'merchantIdentificationValidUntil' => $merchantIdentificationValidUntil,
            'merchantIdentificationExpired'    => $merchantIdentificationValidUntil !== null && $merchantIdentificationValidUntil < new DateTimeImmutable(),
        ];
    }
}
