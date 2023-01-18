<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentHandler\Traits;

use UnzerPayment\Installers\Attributes;
use UnzerSDK\Resources\EmbeddedResources\RiskData;

trait HasRiskDataTrait
{
    private function generateRiskDataResource(): ?RiskData
    {
        $fraudPreventionSessionId = $this->session->offsetGet(Attributes::UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_SESSION_ID);

        if (null === $fraudPreventionSessionId) {
            return null;
        }

        $riskData = new RiskData();
        $riskData->setThreatMetrixId($fraudPreventionSessionId);

        $user = $this->getUser();

        if (null !== $user) {
            $date = $user['additional']['user']['firstlogin'] ? (new \DateTime($user['additional']['user']['firstlogin']))->format('Ymd') : null;
            $riskData->setRegistrationLevel('0' === $user['additional']['user']['accountmode'] ? '1' : '0');
            $riskData->setRegistrationDate($date);
        }

        return $riskData;
    }

    private function unsetFraudSessionId(): void
    {
        $this->session->offsetUnset(Attributes::UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_SESSION_ID);
    }
}
