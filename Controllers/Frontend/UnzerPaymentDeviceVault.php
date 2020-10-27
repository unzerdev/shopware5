<?php

declare(strict_types=1);

class Shopware_Controllers_Frontend_UnzerPaymentDeviceVault extends Enlight_Controller_Action
{
    public function deleteDeviceAction(): void
    {
        $vaultId      = $this->request->get('id');
        $vaultService = $this->container->get('unzer_payment.services.payment_device_vault');
        $userId       = $this->container->get('session')->offsetGet('sUserId');

        if (!$vaultId || !$userId) {
            return;
        }

        $vaultService->deleteDeviceFromVault((int) $userId, (int) $vaultId);

        $this->redirect([
            'controller'             => 'account',
            'action'                 => 'payment',
            'unzerPaymentDeviceRemoved' => true,
        ]);
    }
}
