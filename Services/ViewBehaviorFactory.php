<?php

namespace HeidelPayment\Services;

use HeidelPayment\Services\ViewBehaviorHandler\ViewBehaviorHandlerInterface;

class ViewBehaviorFactory implements ViewBehaviorFactoryInterface
{
    /** @var array<array<ViewBehaviorHandlerInterface>> */
    private $behaviorHandlers;

    /**
     * {@inheritdoc}
     */
    public function getBehaviorHandler(string $paymentName): array
    {
        return $this->behaviorHandlers[$paymentName];
    }

    /**
     * {@inheritdoc}
     */
    public function addBehaviorHandler(ViewBehaviorHandlerInterface $behaviorHandler, string $paymentName): void
    {
        $this->behaviorHandlers[$paymentName][] = $behaviorHandler;
    }
}
