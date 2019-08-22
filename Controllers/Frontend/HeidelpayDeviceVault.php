<?php

class Shopware_Controllers_Frontend_HeidelpayDeviceVault extends Enlight_Controller_Action
{
    public function deleteDeviceAction()
    {
        $vaultId      = $this->request->get('id');
        $vaultService = $this->container->get('heidel_payment.services.payment_device_vault');
        $userId       = $this->container->get('session')->offsetGet('sUserId');

        if (!$vaultId || !$userId) {
            return;
        }

        $vaultService->deleteDeviceFromVault($userId, $vaultId);

        $this->redirect([
            'controller'             => 'account',
            'action'                 => 'payment',
            'heidelpayDeviceRemoved' => true,
        ]);
    }
}
