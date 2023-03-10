<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentHandler\Traits;

use Enlight_Components_Session_Namespace;
use RuntimeException;
use Shopware_Components_Snippet_Manager;
use UnzerPayment\Subscribers\Core\OrderSubscriber;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Zend_Currency;

/**
 * @property Shopware_Components_Snippet_Manager  $snippetManager
 * @property Enlight_Components_Session_Namespace $session
 * @property Charge                               $paymentResult
 * @property Zend_Currency                        $currency
 */
trait OrderComment
{
    public function setOrderComment(string $namespace): void
    {
        if (!($this->paymentResult instanceof Charge || $this->paymentResult instanceof Authorization)) {
            throw new RuntimeException('Can not set order comment without an authorization or charge');
        }

        $snippets = $this->snippetManager->getNamespace($namespace);

        $comment = $snippets->get('message');

        $keyValuePairs = [
            $snippets->get('label/amount')     => html_entity_decode($this->currency->toCurrency($this->paymentResult->getAmount())),
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
