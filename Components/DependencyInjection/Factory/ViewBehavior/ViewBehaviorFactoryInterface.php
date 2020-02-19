<?php

declare(strict_types=1);

namespace HeidelPayment\Components\DependencyInjection\Factory\ViewBehavior;

use HeidelPayment\Components\ViewBehaviorHandler\ViewBehaviorHandlerInterface;

interface ViewBehaviorFactoryInterface
{
    public function getBehaviorHandler(string $paymentName): array;

    public function addBehaviorHandler(ViewBehaviorHandlerInterface $behaviorHandler, string $paymentName): void;
}
