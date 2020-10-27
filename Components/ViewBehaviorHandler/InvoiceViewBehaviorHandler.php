<?php

declare(strict_types=1);

namespace UnzerPayment\Components\ViewBehaviorHandler;

use Enlight_View_Default as View;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Smarty_Data;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;

class InvoiceViewBehaviorHandler implements ViewBehaviorHandlerInterface
{
    /** @var UnzerPaymentClientServiceInterface */
    private $unzerPaymentClient;

    /** @var UnzerPaymentApiLoggerServiceInterface */
    private $apiLoggerService;

    public function __construct(UnzerPaymentClientServiceInterface $unzerPaymentClientService, UnzerPaymentApiLoggerServiceInterface $apiLoggerService)
    {
        $this->unzerPaymentClient = $unzerPaymentClientService;
        $this->apiLoggerService   = $apiLoggerService;
    }

    public function processCheckoutFinishBehavior(View $view, string $transactionId): void
    {
        $charge = $this->getCharge($transactionId);

        if (null === $charge) {
            return;
        }

        $bankData = $this->getBankData($charge);

        $view->assign('bankData', $bankData);
    }

    /**
     * {@inheritdoc}
     */
    public function processDocumentBehavior(Smarty_Data $viewAssignments, string $paymentId, int $documentTypeId): void
    {
        if ($documentTypeId !== static::DOCUMENT_TYPE_INVOICE) {
            return;
        }

        $charge = $this->getCharge($paymentId);

        if (null === $charge) {
            return;
        }

        $bankData = $this->getBankData($charge);

        $viewAssignments->assign('bankData', $bankData, true);
    }

    /**
     * {@inheritdoc}
     */
    public function processEmailVariablesBehavior(string $paymentId): array
    {
        $charge = $this->getCharge($paymentId);

        if (null === $charge) {
            return [];
        }

        return ['bankData' => $this->getBankData($charge)];
    }

    private function getCharge(string $paymentId): ?Charge
    {
        try {
            return $this->unzerPaymentClient->getUnzerPaymentClient()->fetchPayment($paymentId)->getChargeByIndex(0);
        } catch (HeidelpayApiException $apiException) {
            $this->apiLoggerService->logException(sprintf('Error while fetching first charge of payment with payment-id [%s]', $paymentId), $apiException);

            return null;
        }
    }

    private function getBankData(Charge $charge): array
    {
        return [
            'iban'       => $charge->getIban(),
            'bic'        => $charge->getBic(),
            'holder'     => $charge->getHolder(),
            'amount'     => $charge->getAmount(),
            'currency'   => $charge->getCurrency(),
            'descriptor' => $charge->getDescriptor(),
        ];
    }
}
