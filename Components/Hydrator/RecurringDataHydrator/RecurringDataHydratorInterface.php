<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Hydrator\RecurringDataHydrator;

interface RecurringDataHydratorInterface
{
    public function hydrateRecurringData(float $basketAmount, int $orderId): array;
}
