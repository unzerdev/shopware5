<?php

namespace HeidelPayment\Subscribers\Frontend;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use HeidelPayment\Services\DependencyProviderServiceInterface;
use HeidelPayment\Services\PaymentIdentificationServiceInterface;
use HeidelPayment\Services\ViewBehaviorFactoryInterface;
use HeidelPayment\Services\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use HeidelPayment\Services\PaymentVault\PaymentVaultServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;

class Checkout implements SubscriberInterface
{
    /** @var ContextServiceInterface */
    private $contextService;

    /** @var PaymentIdentificationServiceInterface */
    private $paymentIdentificationService;

    /** @var PaymentVaultServiceInterface */
    private $paymentVaultService;

    public function __construct(
        ContextServiceInterface $contextService,
        PaymentIdentificationServiceInterface $paymentIdentificationService,
        DependencyProviderServiceInterface $dependencyProvider,
        ViewBehaviorFactoryInterface $viewBehaviorFactory
    ) {
        $this->contextService               = $contextService;
        $this->paymentIdentificationService = $paymentIdentificationService;
        $this->paymentVaultService          = $paymentVaultService;
        $this->dependencyProvider           = $dependencyProvider;
        $this->viewBehaviorFactory          = $viewBehaviorFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_controller_action_PostDispatchSecure_Frontend_Checkout' => [
                ['onPostDispatchCheckout'],
                ['onPostDispatchShippingPayment'],
                ['onPostDispatchFinish'],
            ],
        ];
    }

    public function onPostDispatchCheckout(ActionEventArgs $args): void
    {
        $request = $args->getRequest();

        if ($request->getActionName() !== 'confirm') {
            return;
        }

        $view                  = $args->getSubject()->View();
        $selectedPaymentMethod = $this->getSelectedPayment();

        if (!$selectedPaymentMethod) {
            return;
        }

        $locale = str_replace('_', '-', $this->contextService->getShopContext()->getShop()->getLocale()->getLocale());
        $view->assign('hasHeidelpayFrame', $this->paymentIdentificationService->isHeidelpayPaymentWithFrame($selectedPaymentMethod));
        $view->assign('heidelpayVault', $this->paymentVaultService->getVaultedDevicesForCurrentUser());
        $view->assign('heidelpayLocale', $locale);
    }

    public function onPostDispatchFinish(ActionEventArgs $args): void
    {
        $request = $args->getRequest();

        if ($request->getActionName() !== 'finish') {
            return;
        }

        $session         = $this->dependencyProvider->getSession();
        $selectedPayment = $this->getSelectedPayment();

        if (!$session->offsetExists('heidelPaymentId') ||
            !$this->paymentIdentificationService->isHeidelpayPayment($selectedPayment)
        ) {
            return;
        }

        $view            = $args->getSubject()->View();
        $heidelPaymentId = $session->offsetGet('heidelPaymentId');

        $viewHandlers = $this->viewBehaviorFactory->getBehaviorHandler($selectedPayment['name']);

        /** @var ViewBehaviorHandlerInterface $behavior */
        foreach ($viewHandlers as $behavior) {
            $behavior->handleFinishPage($view, $heidelPaymentId, ViewBehaviorHandlerInterface::ACTION_FINISH);
        }

        $session->offsetUnset('heidelPaymentId');
    }

    public function onPostDispatchShippingPayment(ActionEventArgs $args): void
    {
        $request = $args->getRequest();

        if ($request->getActionName() !== 'shippingPayment') {
            return;
        }

        $heidelpayMessage = $request->get('heidelpayMessage');
        if (empty($heidelpayMessage)) {
            return;
        }

        $view     = $args->getSubject()->View();
        $messages = (array) $view->getAssign('sErrorMessages');

        $messages[] = $heidelpayMessage;

        $view->assign('sErrorMessages', $messages);
    }

    private function getSelectedPayment(): array
    {
        return $this->dependencyProvider->getSession()->offsetGet('sOrderVariables')['sUserData']['additional']['payment'];
    }
}
