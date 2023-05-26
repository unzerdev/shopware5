<?php

declare(strict_types=1);

use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_ApplePayCertificateManager extends Enlight_Controller_Action implements CSRFWhitelistAware{

    public function preDispatch(): void
    {
        $this->get('template')->addTemplateDir(__DIR__ . '/../../Resources/views/');
    }


    public function indexAction() {

    }

    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }
}
