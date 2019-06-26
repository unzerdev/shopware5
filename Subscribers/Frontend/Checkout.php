<?php

namespace HeidelPayment\Subscribers\Frontend;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;

class Checkout implements SubscriberInterface
{
    /** @var ContextServiceInterface */
    private $contextService;

    public function __construct(ContextServiceInterface $contextService)
    {
        $this->contextService = $contextService;
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
        $selectedPaymentMethod = $view->getAssign('sPayment');

        if (!$selectedPaymentMethod) {
            return;
        }

        $locale = str_replace('_', '-', $this->contextService->getShopContext()->getShop()->getLocale()->getLocale());
        $view->assign('hasHeidelpayFrame', strpos($selectedPaymentMethod['name'], 'heidel') !== false && !empty($selectedPaymentMethod['embediframe']));
        $view->assign('heidelLocale', $locale);
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
}
