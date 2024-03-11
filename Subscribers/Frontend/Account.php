<?php

declare(strict_types=1);

namespace UnzerPayment\Subscribers\Frontend;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use UnzerPayment\Services\PaymentVault\PaymentVaultServiceInterface;

class Account implements SubscriberInterface
{
    private PaymentVaultServiceInterface $paymentVaultService;

    public function __construct(PaymentVaultServiceInterface $paymentVaultService)
    {
        $this->paymentVaultService = $paymentVaultService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Account' => 'onPostDispatchAccount',
        ];
    }

    public function onPostDispatchAccount(ActionEventArgs $args): void
    {
        if ($args->getRequest()->getActionName() !== 'payment') {
            return;
        }

        $view     = $args->getSubject()->View();
        $userData = $view->getAssign('sUserData');

        $view->assign('unzerPaymentDeviceRemoved', $args->getRequest()->get('unzerPaymentDeviceRemoved'));
        $vaultedDevices = $this->paymentVaultService->getVaultedDevicesForCurrentUser($userData['billingaddress'], $userData['shippingaddress']);

        if (!empty($vaultedDevices)) {
            $view->assign('unzerPaymentVault', $vaultedDevices);
        }
    }
}
