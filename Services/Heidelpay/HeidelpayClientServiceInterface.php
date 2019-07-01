<?php

namespace HeidelPayment\Services\Heidelpay;

use heidelpayPHP\Heidelpay;

interface HeidelpayClientServiceInterface
{
    public function getHeidelpayClient(): Heidelpay;
}
