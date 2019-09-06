<?php

namespace HeidelPayment\Components;

final class BookingMode
{
    const CHARGE             = 'charge';
    const AUTHORIZE          = 'authorize';
    const CHARGE_REGISTER    = 'registerCharge';
    const AUTHORIZE_REGISTER = 'registerAuthorize';
}
