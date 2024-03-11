<?php

declare(strict_types=1);

namespace UnzerPayment\Subscribers\Core;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;

class OrderSubscriber implements SubscriberInterface
{
    public const ORDER_COMMENT_SESSION_KEY = 'unzerOrderComment';

    private Enlight_Components_Session_Namespace $session;

    public function __construct(Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SaveOrder_FilterParams' => 'addComment',
        ];
    }

    public function addComment(\Enlight_Event_EventArgs $args): void
    {
        if (!$this->session->offsetExists(self::ORDER_COMMENT_SESSION_KEY)) {
            return;
        }

        $params = $args->getReturn();

        $params['comment'] = $this->session->offsetGet(self::ORDER_COMMENT_SESSION_KEY);

        $this->session->offsetUnset(self::ORDER_COMMENT_SESSION_KEY);

        $args->setReturn($params);
    }
}
