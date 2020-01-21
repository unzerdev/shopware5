<?php

declare(strict_types=1);

namespace HeidelPayment\Subscribers\Documents;

use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs as HookEventArgs;
use HeidelPayment\Installers\PaymentMethods;
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

    public function __construct(
        PaymentIdentificationServiceInterface $paymentIdentificationService,
        ViewBehaviorFactoryInterface $viewBehaviorFactory
    ) {
        $this->paymentIdentificationService = $paymentIdentificationService;
        $this->viewBehaviorFactory          = $viewBehaviorFactory;
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

    public function onRenderDocument(HookEventArgs $args): void
    {
        /** @var Shopware_Components_Document $subject */
        $subject             = $args->getSubject();
        $view                = $subject->_view;
        $orderData           = $view->getTemplateVars('Order');
        $selectedPayment     = $orderData['_payment'];
        $selectedPaymentName = $orderData['_payment']['name'];
        $heidelPaymentId     = $orderData['_order']['temporaryID'];
        $docType             = (int) $subject->_typID;

        if (empty($heidelPaymentId) || !$this->paymentIdentificationService->isHeidelpayPayment($selectedPayment)) {
            return;
        }

        $behaviors = $this->viewBehaviorFactory->getBehaviorHandler($selectedPayment['name']);

        /** @var ViewBehaviorHandlerInterface $behavior */
        foreach ($behaviors as $behavior) {
            $behavior->processDocumentBehavior($view, $heidelPaymentId, $docType);
        }

        if (in_array($selectedPaymentName, [
            PaymentMethods::PAYMENT_NAME_INVOICE,
            PaymentMethods::PAYMENT_NAME_INVOICE_FACTORING,
            PaymentMethods::PAYMENT_NAME_INVOICE_GUARANTEED,
        ])) {
            $view->assign('heidelPaymentIsInvoice', true);
        }

        if ($selectedPaymentName === PaymentMethods::PAYMENT_NAME_PRE_PAYMENT) {
            $view->assign('heidelPaymentIsPrePayment', true);
        }
    }
}
