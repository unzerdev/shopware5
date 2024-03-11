<?php

declare(strict_types=1);

namespace UnzerPayment\Subscribers\Core;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use UnzerPayment\Services\UnzerAsyncOrderBackupService;

class SaveOrderSubscriber implements SubscriberInterface
{
    private Enlight_Components_Session_Namespace $session;

    public function __construct(Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return ['Shopware_Modules_Order_SaveOrder_FilterParams' => 'fixOrderFilterParams'];
    }

    public function fixOrderFilterParams(\Enlight_Event_EventArgs $args): array
    {
        $orderParams = $args->getReturn();

        if ($this->session->offsetExists(UnzerAsyncOrderBackupService::UNZER_ASYNC_SESSION_SUBSHOP_ID)
            && !empty($this->session->get(UnzerAsyncOrderBackupService::UNZER_ASYNC_SESSION_SUBSHOP_ID))
            && $this->session->get(UnzerAsyncOrderBackupService::UNZER_ASYNC_SESSION_SUBSHOP_ID) !== 0) {
            // Fix for shopware due to saving the subShopId inside the language field
            $orderParams['language'] = (int) $this->session->get(UnzerAsyncOrderBackupService::UNZER_ASYNC_SESSION_SUBSHOP_ID);
            $this->session->offsetUnset(UnzerAsyncOrderBackupService::UNZER_ASYNC_SESSION_SUBSHOP_ID);
        }

        return $orderParams;
    }
}
