<?php

declare(strict_types=1);

namespace HeidelPayment\Subscribers\Frontend;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs as EventArgs;

class Template implements SubscriberInterface
{
    /** @var string */
    private $pluginDir;

    public function __construct(string $pluginDir)
    {
        $this->pluginDir = $pluginDir;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Theme_Inheritance_Template_Directories_Collected' => 'onCollectTemplateDirs',
        ];
    }

    public function onCollectTemplateDirs(EventArgs $args): void
    {
        $dirs   = $args->getReturn();
        $dirs[] = $this->pluginDir . '/Resources/views';

        $args->setReturn($dirs);
    }
}
