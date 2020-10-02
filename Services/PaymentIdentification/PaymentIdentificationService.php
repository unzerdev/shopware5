<?php

declare(strict_types=1);

namespace HeidelPayment\Services\PaymentIdentification;

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Installers\Attributes;
use HeidelPayment\Installers\PaymentMethods;
use HeidelPayment\Services\ConfigReader\ConfigReaderServiceInterface;

class PaymentIdentificationService implements PaymentIdentificationServiceInterface
{
    /** @var ConfigReaderServiceInterface */
    private $configReader;

    public function __construct(ConfigReaderServiceInterface $configReader)
    {
        $this->configReader = $configReader;
    }

    /**
     * {@inheritdoc}
     */
    public function isHeidelpayPayment(array $payment): bool
    {
        return strpos($payment['name'], 'heidel') === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isHeidelpayPaymentWithFrame(array $payment): bool
    {
        return strpos($payment['name'], 'heidel') !== false &&
            !empty($payment['attributes']) &&
            !empty($payment['attributes']['core']) &&
            !empty($payment['attributes']['core']->get(Attributes::HEIDEL_ATTRIBUTE_PAYMENT_FRAME) &&
            $this->shouldDisplayFrame($payment['name']));
    }

    private function shouldDisplayFrame(string $paymentName): bool
    {
        if ($paymentName === PaymentMethods::PAYMENT_NAME_PAYPAL &&
            !in_array($this->configReader->get('paypal_bookingmode'), [BookingMode::AUTHORIZE_REGISTER, BookingMode::CHARGE_REGISTER])) {
            return false;
        }

        return true;
    }
}
