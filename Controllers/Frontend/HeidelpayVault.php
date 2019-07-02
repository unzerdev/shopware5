<?php

class Shopware_Controllers_Frontend_HeidelpayVault extends Enlight_Controller_Action
{
    public function deleteDeviceAction()
    {
        $vaultId      = $this->request->get('id');
        $vaultService = $this->container->get('heidel_payment.services.device_vault');
        $userId       = $this->container->get('session')->offsetGet('sUserId');

        $vaultService->deleteDeviceFromVault($userId, $vaultId);

        $this->redirect([
            'controller'             => 'account',
            'action'                 => 'payment',
            'heidelpayDeviceRemoved' => true,
        ]);
    }
}
