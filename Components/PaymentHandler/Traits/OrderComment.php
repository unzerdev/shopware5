<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentHandler\Traits;

use RuntimeException;
use UnzerPayment\Subscribers\Core\OrderSubscriber;
use UnzerSDK\Resources\TransactionTypes\Charge;

/**
 * @property \Shopware_Components_Snippet_Manager  $snippetManager
 * @property \Enlight_Components_Session_Namespace $session
 * @property Charge                                $paymentResult
 */
trait OrderComment
{
    public function setOrderComment(string $namespace): void
    {
        if (!($this->paymentResult instanceof Charge)) {
            throw new RuntimeException('Can not set order comment without a charge');
        }

        $snippets = $this->snippetManager->getNamespace($namespace);

        $comment = $snippets->get('message');

        $keyValuePairs = [
            $snippets->get('label/amount')     => $this->paymentResult->getAmount(),
            $snippets->get('label/recipient')  => $this->paymentResult->getHolder(),
            $snippets->get('label/iban')       => $this->paymentResult->getIban(),
            $snippets->get('label/bic')        => $this->paymentResult->getBic(),
            $snippets->get('label/descriptor') => $this->paymentResult->getDescriptor(),
        ];

        foreach ($keyValuePairs as $key => $value) {
            $comment .= sprintf("\n%s: %s", $key, $value);
        }

        $this->session->offsetSet(OrderSubscriber::ORDER_COMMENT_SESSION_KEY, $comment);
    }
}
