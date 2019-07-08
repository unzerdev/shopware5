<?php

namespace HeidelPayment\Services;

use HeidelPayment\Services\BehaviorHandler\BehaviorHandlerInterface;

interface BehaviorFactoryInterface
{
    public function getBehaviorHandler(string $paymentName);

    public function addBehaviorHandler(BehaviorHandlerInterface $behaviorHandler);
}