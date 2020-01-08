<?php

declare(strict_types=1);

namespace HeidelPayment\Services;

use HeidelPayment\Services\ViewBehaviorHandler\ViewBehaviorHandlerInterface;

class ViewBehaviorFactory implements ViewBehaviorFactoryInterface
{
    /** @var array */
    private $behaviorHandlers;

    /**
     * {@inheritdoc}
     */
    public function getBehaviorHandler(string $paymentName): array
    {
        if (!array_key_exists($paymentName, $this->behaviorHandlers)) {
            return [];
        }

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
