<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper;

use Shopware_Components_Snippet_Manager;
use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\Applepay;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Card;

class ApplePayStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
{
    /** @var Shopware_Components_Snippet_Manager */
    protected $snippetManager;

    /** @var ConfigReaderServiceInterface */
    protected $configReader;

    public function __construct(
        Shopware_Components_Snippet_Manager $snippetManager,
        ConfigReaderServiceInterface $configReader
    ) {
        $this->snippetManager = $snippetManager;
        $this->configReader   = $configReader;
    }

    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Applepay;
    }

    public function getTargetPaymentStatus(Payment $paymentObject, ?bool $isWebhook = false): int
    {
        if ($isWebhook) {
            return $this->mapPaymentStatus($paymentObject);
        }

        if ($paymentObject->isPending()
            && $this->configReader->get('apple_pay_bookingmode') !== 'charge'
            && $this->configReader->get('apple_pay_bookingmode') !== 'authorize'
        ) {
            throw new StatusMapperException(Card::getResourceName(), $paymentObject->getStateName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_STATUS) {
                return $status;
            }

            throw new StatusMapperException(Applepay::getResourceName(), $paymentObject->getStateName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
