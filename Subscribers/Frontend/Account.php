<?php

namespace HeidelPayment\Subscribers\Frontend;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use HeidelPayment\Services\PaymentVault\PaymentVaultServiceInterface;

class Account implements SubscriberInterface
{
    /** @var PaymentVaultServiceInterface */
    private $paymentVaultService;

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

    public function onPostDispatchAccount(ActionEventArgs $args)
    {
        if ($args->getRequest()->getActionName() !== 'payment') {
            return;
        }

        $view     = $args->getSubject()->View();
        $userData = $view->getAssign('sUserData');

        $view->assign('heidelpayDeviceRemoved', $args->getRequest()->get('heidelpayDeviceRemoved'));
        $vaultedDevices = $this->paymentVaultService->getVaultedDevicesForCurrentUser($userData['billingaddress'], $userData['shippingaddress']);

        if (!empty($vaultedDevices)) {
            $view->assign('heidelpayVault', $vaultedDevices);
        }
    }
}
