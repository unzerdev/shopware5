<?php

declare(strict_types=1);

namespace HeidelPayment\Subscribers\Documents;

use Doctrine\DBAL\Connection;
use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs as HookEventArgs;
use HeidelPayment\Installers\PaymentMethods;
use HeidelPayment\Services\PaymentIdentificationServiceInterface;
use HeidelPayment\Services\ViewBehaviorFactoryInterface;
use HeidelPayment\Services\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use Shopware_Components_Document;
use Shopware_Components_Translation;

class Invoice implements SubscriberInterface
{
    private const INVOICE_PAYMENT_METHODS = [
        PaymentMethods::PAYMENT_NAME_INVOICE,
        PaymentMethods::PAYMENT_NAME_INVOICE_FACTORING,
        PaymentMethods::PAYMENT_NAME_INVOICE_GUARANTEED,
    ];

    /** @var PaymentIdentificationServiceInterface */
    private $paymentIdentificationService;

    /** @var ViewBehaviorFactoryInterface */
    private $viewBehaviorFactory;

    /** @var Connection */
    private $connection;

    /** @var Shopware_Components_Translation */
    private $translationComponent;

    public function __construct(
        PaymentIdentificationServiceInterface $paymentIdentificationService,
        ViewBehaviorFactoryInterface $viewBehaviorFactory,
        Connection $connection,
        Shopware_Components_Translation $translationComponent
    ) {
        $this->paymentIdentificationService = $paymentIdentificationService;
        $this->viewBehaviorFactory          = $viewBehaviorFactory;
        $this->connection                   = $connection;
        $this->translationComponent         = $translationComponent;
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
        $orderData           = $view->getTemplateVars('Order');
        $selectedPayment     = $orderData['_payment'];
        $selectedPaymentName = $orderData['_payment']['name'];
        $heidelPaymentId     = $orderData['_order']['temporaryID'];
        $docType             = (int) $subject->_typID;

        if (empty($heidelPaymentId) || !$this->paymentIdentificationService->isHeidelpayPayment($selectedPayment)) {
            return;
        }

        $behaviors = $this->viewBehaviorFactory->getBehaviorHandler($selectedPayment['name']);

        /** @var ViewBehaviorHandlerInterface $behavior */
        foreach ($behaviors as $behavior) {
            $behavior->processDocumentBehavior($view, $heidelPaymentId, $docType);
        }

        if (in_array($selectedPaymentName, self::INVOICE_PAYMENT_METHODS)) {
            $view->assign('heidelPaymentIsInvoice', true);
            $view->assign('CustomDocument', $this->getDocumentData($docType, (int) $subject->_order->order->language));
        }

        if ($selectedPaymentName === PaymentMethods::PAYMENT_NAME_PRE_PAYMENT) {
            $view->assign('heidelPaymentIsPrePayment', true);
            $view->assign('CustomDocument', $this->getDocumentData($docType, (int) $subject->_order->order->language));
        }
    }

    private function getDocumentData(int $typId, int $orderLanguage): array
    {
        $customDocument         = [];
        $translation            = $this->translationComponent->read($orderLanguage, 'documents');
        $heidelPaymentTemplates = $this->connection->createQueryBuilder()
            ->select(['name', 'value', 'style'])
            ->from('s_core_documents_box')
            ->where('name LIKE "HeidelPayment%"')
            ->andWhere('documentId = :typId')
            ->setParameter('typId', $typId)
            ->execute()->fetchAll();

        foreach ($heidelPaymentTemplates as $heidelPaymentTemplate) {
            $customDocument[$heidelPaymentTemplate['name']] = [
                'value' => $heidelPaymentTemplate['value'],
                'style' => $heidelPaymentTemplate['style'],
            ];

            $valueTranslation = $translation[$heidelPaymentTemplate['name'] . '_Value'];

            if (!empty($valueTranslation)) {
                $customDocument[$heidelPaymentTemplate['name']]['value'] = $valueTranslation;
            }

            $styleTranslation = $translation[$heidelPaymentTemplate['name'] . '_Style'];

            if (!empty($valueTranslation)) {
                $customDocument[$heidelPaymentTemplate['name']]['style'] = $styleTranslation;
            }
        }

        return $customDocument;
    }
}
