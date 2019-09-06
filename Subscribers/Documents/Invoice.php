<?php

namespace HeidelPayment\Subscribers\Documents;

use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs as HookEventArgs;
use HeidelPayment\Services\PaymentIdentificationServiceInterface;
use HeidelPayment\Services\ViewBehaviorFactoryInterface;
use HeidelPayment\Services\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use Shopware_Components_Document;

class Invoice implements SubscriberInterface
{
    /** @var PaymentIdentificationServiceInterface */
    private $paymentIdentificationService;

    /** @var ViewBehaviorFactoryInterface */
    private $viewBehaviorFactory;

    /** @var string */
    private $pluginDir;

    public function __construct(PaymentIdentificationServiceInterface $paymentIdentificationService, ViewBehaviorFactoryInterface $viewBehaviorFactory, string $pluginDir)
    {
        $this->paymentIdentificationService = $paymentIdentificationService;
        $this->viewBehaviorFactory          = $viewBehaviorFactory;
        $this->pluginDir                    = $pluginDir;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Components_Document::assignValues::after' => 'onRenderDocument',
        ];
    }

    public function onRenderDocument(HookEventArgs $args)
    {
        /** @var Shopware_Components_Document $subject */
        $subject             = $args->getSubject();
        $view                = $subject->_view;
        $orderData           = $view->getTemplateVars('Order');
        $selectedPayment     = $orderData['_payment'];
        $selectedPaymentName = $orderData['_payment']['name'];
        $heidelPaymentId     = $orderData['_order']['temporaryID'];
        $docType             = (int) $subject->_typID;

        if (empty($heidelPaymentId) ||
            !$this->paymentIdentificationService->isHeidelpayPayment($selectedPayment)
        ) {
            return;
        }

        $behaviors            = $this->viewBehaviorFactory->getBehaviorHandler($selectedPayment['name']);
        $behaviorTemplatePath = sprintf('%s/Resources/views/frontend/heidelpay/behaviors/%s/document.tpl', $this->pluginDir, $selectedPaymentName);
        $behaviorTemplate     = sprintf('frontend/heidelpay/behaviors/%s/document.tpl', $selectedPaymentName);

        /** @var ViewBehaviorHandlerInterface $behavior */
        foreach ($behaviors as $behavior) {
            $behavior->processDocumentBehavior($view, $heidelPaymentId, $docType);
        }

        if (file_exists($behaviorTemplatePath)) {
            $view->assign('heidelBehaviorTemplate', $behaviorTemplate);
        }
    }
}
