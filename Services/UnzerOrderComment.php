<?php

declare(strict_types=1);

namespace UnzerPayment\Services;

use Enlight_Components_Session_Namespace;
use Enlight_Components_Snippet_Manager;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerPayment\Installers\PaymentMethods;
use UnzerPayment\Subscribers\Core\OrderSubscriber;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;
use Zend_Currency;

class UnzerOrderComment
{
    public const PAYMENT_METHOD_SNIPPET_MAPPING = [
        PaymentMethods::PAYMENT_NAME_PRE_PAYMENT      => AbstractUnzerPaymentController::PREPAYMENT_SNIPPET_NAMESPACE,
        PaymentMethods::PAYMENT_NAME_INVOICE          => AbstractUnzerPaymentController::INVOICE_SNIPPET_NAMESPACE,
        PaymentMethods::PAYMENT_NAME_INVOICE_SECURED  => AbstractUnzerPaymentController::INVOICE_SNIPPET_NAMESPACE,
        PaymentMethods::PAYMENT_NAME_PAYLATER_INVOICE => AbstractUnzerPaymentController::PAYLATER_INVOICE_SNIPPET_NAMESPACE,
    ];

    /** @var Enlight_Components_Snippet_Manager */
    private $snippetManager;

    /** @var Enlight_Components_Session_Namespace */
    private $session;

    /** @var Zend_Currency */
    private $currency;

    public function __construct(Enlight_Components_Snippet_Manager $snippetManager, Enlight_Components_Session_Namespace $session, Zend_Currency $currency)
    {
        $this->snippetManager = $snippetManager;
        $this->session        = $session;
        $this->currency       = $currency;
    }

    /**
     * @param Authorization|Charge $payment
     */
    public function setOrderCommentBeforeSavingOrder($payment, string $snippetNamespace): void
    {
        $snippets = $this->snippetManager->getNamespace($snippetNamespace);

        $comment = $snippets->get('message');

        $keyValuePairs = [
            $snippets->get('label/amount')     => html_entity_decode($this->currency->toCurrency($payment->getAmount())),
            $snippets->get('label/recipient')  => $payment->getHolder(),
            $snippets->get('label/iban')       => $payment->getIban(),
            $snippets->get('label/bic')        => $payment->getBic(),
            $snippets->get('label/descriptor') => $payment->getDescriptor(),
        ];

        foreach ($keyValuePairs as $key => $value) {
            $comment .= sprintf("\n%s: %s", $key, $value);
        }

        $this->session->offsetSet(OrderSubscriber::ORDER_COMMENT_SESSION_KEY, $comment);
    }
}
