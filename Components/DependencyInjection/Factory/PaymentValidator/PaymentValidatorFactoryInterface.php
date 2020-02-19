<?php

declare(strict_types=1);

namespace HeidelPayment\Components\DependencyInjection\Factory\PaymentValidator;

use HeidelPayment\Components\PaymentValidator\PaymentValidatorInterface;

interface PaymentValidatorFactoryInterface
{
    public function getBehaviorHandler(string $paymentName): ?PaymentValidatorInterface;

    public function addBehaviorHandler(PaymentValidatorInterface $behaviorHandler, string $paymentName): void;
}
