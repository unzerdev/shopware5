<?php

declare(strict_types=1);

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Shop\Shop;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerSDK\Unzer;

class Shopware_Controllers_Backend_ApplePayCertificateManager extends Enlight_Controller_Action implements CSRFWhitelistAware{


    /** @var Shop */
    private $shop;

    /** @var \Shopware\Components\Model\ModelManager */
    private $modelManager;

    /** @var Unzer */
    private $unzerPaymentClient;

    /** @var UnzerPaymentApiLoggerServiceInterface */
    private $logger;

    public function preDispatch(): void
    {
        $this->get('template')->addTemplateDir(__DIR__ . '/../../Resources/views/');

        $this->modelManager                       = $this->container->get('models');
        $shopId                             = $this->request->get('shopId');
        $unzerPaymentClientService          = $this->container->get('unzer_payment.services.api_client');
        $this->logger                       = $this->container->get('unzer_payment.services.api_logger');

        if ($shopId) {
            $this->shop = $this->modelManager->find(Shop::class, $shopId);
        } else {
            $this->shop = $this->modelManager->getRepository(Shop::class)->getActiveDefault();
        }


        if ($this->shop === null) {
            throw new RuntimeException('Could not determine shop context');
        }

        $locale                   = $this->container->get('locale')->toString();
        $this->unzerPaymentClient = $unzerPaymentClientService->getUnzerPaymentClient($locale, $shopId !== null ? (int) $shopId : null);

        if ($this->unzerPaymentClient === null) {
            $this->logger->getPluginLogger()->error('Could not initialize the Unzer Payment client');
        }
    }

    public function postDispatch()
    {
        $csrfToken = $this->container->get('backendsession')->offsetGet('X-CSRF-Token');

        $this->View()->assign([
            'csrfToken' => $csrfToken,
            'shopId' => $this->shop->getId(),
            'isDefaultShop' => $this->shop->getDefault(),
            'shops' => $this->modelManager->getRepository(Shop::class)->findAll()
        ]);
    }

    public function indexAction() {

        if ($this->request->get('viewData')) {
            $this->View()->assign($this->request->get('viewData'));
        }
    }

    public function updateCertificatesAction() {
        $this->forward('index', 'ApplePayCertificateManager', 'backend', [
            'viewData' => [
                'paymentProcessingCertificateUpdated' => true,
                'merchantCertificateUpdated' => true
            ]
        ]);
    }

    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }
}
