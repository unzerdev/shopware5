<?php

declare(strict_types=1);

namespace UnzerPayment\Components;

final class BookingMode
{
    public const CHARGE             = 'charge';
    public const AUTHORIZE          = 'authorize';
    public const CHARGE_REGISTER    = 'registerCharge';
    public const AUTHORIZE_REGISTER = 'registerAuthorize';
}
