<?php

declare(strict_types=1);

namespace HeidelPayment\Subscribers\Backend;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Shopware_Controllers_Backend_Order;

class Template implements SubscriberInterface
{
    /** @var string */
    private $pluginDir;

    public function __construct(string $pluginDir)
    {
        $this->pluginDir = $pluginDir;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order'  => 'onLoadOrderTemplate',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Config' => 'onPostDispatchConfig',
        ];
    }

    public function onLoadOrderTemplate(ActionEventArgs $args): void
    {
        /** @var Shopware_Controllers_Backend_Order $controller */
        $controller = $args->getSubject();
        $view       = $controller->View();
        $request    = $controller->Request();

        if (!$view) {
            return;
        }

        $view->addTemplateDir($this->pluginDir . '/Resources/views');

        if ($request->getActionName() === 'index') {
            $view->extendsTemplate('backend/heidel_payment/app.js');
        }

        if ($request->getActionName() === 'load') {
            $view->extendsTemplate('backend/heidel_payment/view/detail/window.js');
        }
    }

    public function onPostDispatchConfig(ActionEventArgs $args): void
    {
        $view = $args->getSubject()->View();

        if (!$view) {
            return;
        }

        if ($args->getRequest()->getActionName() === 'load') {
            $view->addTemplateDir($this->pluginDir . '/Resources/views/');
            $view->extendsTemplate('backend/config/view/form/document_heidel_payment.js');
        }
    }
}
