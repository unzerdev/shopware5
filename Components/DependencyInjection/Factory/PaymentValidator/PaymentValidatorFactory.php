<?php

declare(strict_types=1);

namespace HeidelPayment\Components\DependencyInjection\Factory\PaymentValidator;

use HeidelPayment\Components\PaymentValidator\PaymentValidatorInterface;

class PaymentValidatorFactory implements PaymentValidatorFactoryInterface
{
    /** @var PaymentValidatorInterface[] */
    protected $paymentValidator;

    public function getBehaviorHandler(string $paymentName): PaymentValidatorInterface
    {
        if (!array_key_exists($paymentName, $this->paymentValidator)) {
            return [];
        }

        return $this->paymentValidator[$paymentName];
    }

    public function addBehaviorHandler(PaymentValidatorInterface $paymentValidator, string $paymentName): void
    {
        $this->paymentValidator[$paymentName] = $paymentValidator;
    }
}
