<?php

declare(strict_types=1);

namespace UnzerPayment\Components\Converter\BasketConverter;

interface BasketConverterInterface
{
    public function populateDeprecatedVariables(int $orderId, array $basket): array;
}
