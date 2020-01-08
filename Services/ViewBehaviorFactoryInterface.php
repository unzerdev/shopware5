<?php

declare(strict_types=1);

namespace HeidelPayment\Services;

use HeidelPayment\Services\ViewBehaviorHandler\ViewBehaviorHandlerInterface;

interface ViewBehaviorFactoryInterface
{
    public function getBehaviorHandler(string $paymentName): array;

    public function addBehaviorHandler(ViewBehaviorHandlerInterface $behaviorHandler, string $paymentName): void;
}
