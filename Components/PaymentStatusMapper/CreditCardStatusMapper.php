<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper;

use Shopware_Components_Snippet_Manager;
use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Card;

class CreditCardStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
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
        return $paymentType instanceof Card;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): int
    {
        if ($paymentObject->isPending()
            && $this->configReader->get('credit_card_bookingmode') !== 'authorize'
            && $this->configReader->get('credit_card_bookingmode') !== 'registerAuthorize'
        ) {
            throw new StatusMapperException(Card::getResourceName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_STATUS) {
                return $status;
            }

            throw new StatusMapperException(Card::getResourceName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
