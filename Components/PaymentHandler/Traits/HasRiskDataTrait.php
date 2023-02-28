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
            $riskData->setRegistrationLevel($this->getRegistrationLevel($user));
            $riskData->setRegistrationDate($this->getRegistrationDate($user));
        }

        return $riskData;
    }

    private function unsetFraudSessionId(): void
    {
        $this->session->offsetUnset(Attributes::UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_SESSION_ID);
    }

    private function getRegistrationLevel(array $user): string
    {
        $registrationLevelGuest      = '0';
        $registrationLevelRegistered = '1';

        $registrationLevel = $user['additional']['user']['accountmode'];

        return $registrationLevel === $registrationLevelRegistered ? $registrationLevelRegistered : $registrationLevelGuest;
    }

    private function getRegistrationDate($user): ?string
    {
        return $user['additional']['user']['firstlogin'] ? (new \DateTime($user['additional']['user']['firstlogin']))->format('Ymd') : null;
    }
}
