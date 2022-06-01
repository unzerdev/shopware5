<?php

declare(strict_types=1);

namespace UnzerPayment\Subscribers\Core;

use Enlight\Event\SubscriberInterface;
use UnzerPayment\Services\UnzerAsyncOrderBackupService;

class SaveOrderSubscriber implements SubscriberInterface
{
    /** @var \Enlight_Components_Session_Namespace */
    private $session;

    public function __construct(\Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return ['Shopware_Modules_Order_SaveOrder_FilterParams' => 'fixOrderFilterParams'];
    }

    public function fixOrderFilterParams(\Enlight_Event_EventArgs $args): array
    {
        $subshopId   = null;
        $orderParams = $args->getReturn();

        if ($this->session->offsetExists(UnzerAsyncOrderBackupService::UNZER_ASYNC_SESSION_SUBSHOP_ID)) {
            $subshopId = $this->session->get(UnzerAsyncOrderBackupService::UNZER_ASYNC_SESSION_SUBSHOP_ID);
            $this->session->offsetUnset(UnzerAsyncOrderBackupService::UNZER_ASYNC_SESSION_SUBSHOP_ID);
        }

        if ($subshopId !== null) {
            $orderParams['subshopID'] = (int) $subshopId;
        }

        return $orderParams;
    }
}
