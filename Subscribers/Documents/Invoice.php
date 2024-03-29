<?php

declare(strict_types=1);

namespace UnzerPayment\Subscribers\Documents;

use Doctrine\DBAL\Connection;
use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs as HookEventArgs;
use Shopware_Components_Document;
use Shopware_Components_Translation;
use UnzerPayment\Components\DependencyInjection\Factory\ViewBehavior\ViewBehaviorFactoryInterface;
use UnzerPayment\Components\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use UnzerPayment\Installers\PaymentMethods;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use UnzerPayment\Services\PaymentIdentification\PaymentIdentificationServiceInterface;

class Invoice implements SubscriberInterface
{
    private const INVOICE_PAYMENT_METHODS = [
        PaymentMethods::PAYMENT_NAME_INVOICE,
        PaymentMethods::PAYMENT_NAME_INVOICE_SECURED,
        PaymentMethods::PAYMENT_NAME_PAYLATER_INVOICE,
    ];

    private PaymentIdentificationServiceInterface $paymentIdentificationService;

    private ViewBehaviorFactoryInterface $viewBehaviorFactory;

    private Connection $connection;

    private Shopware_Components_Translation $translationComponent;

    private ConfigReaderServiceInterface $configReader;

    public function __construct(
        PaymentIdentificationServiceInterface $paymentIdentificationService,
        ViewBehaviorFactoryInterface $viewBehaviorFactory,
        Connection $connection,
        Shopware_Components_Translation $translationComponent,
        ConfigReaderServiceInterface $configReader
    ) {
        $this->paymentIdentificationService = $paymentIdentificationService;
        $this->viewBehaviorFactory          = $viewBehaviorFactory;
        $this->connection                   = $connection;
        $this->translationComponent         = $translationComponent;
        $this->configReader                 = $configReader;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Components_Document::assignValues::after' => 'onRenderDocument',
        ];
    }

    public function onRenderDocument(HookEventArgs $args): void
    {
        /** @var Shopware_Components_Document $subject */
        $subject             = $args->getSubject();
        $view                = $subject->_view;
        $orderData           = (array) $view->getTemplateVars('Order');
        $selectedPayment     = $orderData['_payment'];
        $selectedPaymentName = $orderData['_payment']['name'];
        $unzerPaymentId      = $orderData['_order']['temporaryID'];
        $docType             = (int) $subject->_typID;

        if (empty($unzerPaymentId) || !$this->paymentIdentificationService->isUnzerPayment($selectedPayment)) {
            return;
        }

        if ($docType === ViewBehaviorHandlerInterface::DOCUMENT_TYPE_INVOICE && PaymentMethods::PAYMENT_NAME_PAYLATER_INSTALLMENT === $selectedPaymentName) {
            $view->assign('showUnzerPaymentInstallmentInfo', true);
        }

        $behaviors = $this->viewBehaviorFactory->getDocumentSupportedBehaviorHandler($selectedPayment['name'], $docType);

        if (empty($behaviors)) {
            return;
        }

        /** @var ViewBehaviorHandlerInterface $behavior */
        foreach ($behaviors as $behavior) {
            $behavior->processDocumentBehavior($view, $unzerPaymentId, $docType);
        }

        if ($this->isPopulateAllowed($selectedPaymentName)) {
            $view->assign('isUnzerPaymentPopulateAllowed', true);
            $view->assign('CustomDocument', $this->getDocumentData($docType, (int) $subject->_order->order->language));
        }
    }

    private function getDocumentData(int $typId, int $orderLanguage): array
    {
        $customDocument        = [];
        $translation           = $this->translationComponent->read($orderLanguage, 'documents');
        $unzerPaymentTemplates = $this->connection->createQueryBuilder()
            ->select(['name', 'value', 'style'])
            ->from('s_core_documents_box')
            ->where('name LIKE "UnzerPayment%"')
            ->andWhere('documentId = :typId')
            ->setParameter('typId', $typId)
            ->execute()->fetchAll();

        foreach ($unzerPaymentTemplates as $unzerPaymentTemplate) {
            $customDocument[$unzerPaymentTemplate['name']] = [
                'value' => $unzerPaymentTemplate['value'],
                'style' => $unzerPaymentTemplate['style'],
            ];

            $valueTranslation = $translation[$unzerPaymentTemplate['name'] . '_Value'];

            if (!empty($valueTranslation)) {
                $customDocument[$unzerPaymentTemplate['name']]['value'] = $valueTranslation;
            }

            $styleTranslation = $translation[$unzerPaymentTemplate['name'] . '_Style'];

            if (!empty($valueTranslation)) {
                $customDocument[$unzerPaymentTemplate['name']]['style'] = $styleTranslation;
            }
        }

        return $customDocument;
    }

    private function isPopulateAllowed(string $paymentName): bool
    {
        return ($paymentName === PaymentMethods::PAYMENT_NAME_PRE_PAYMENT && $this->configReader->get('populate_document_prepayment'))
            || (in_array($paymentName, self::INVOICE_PAYMENT_METHODS, true) && $this->configReader->get('populate_document_invoice'));
    }
}
