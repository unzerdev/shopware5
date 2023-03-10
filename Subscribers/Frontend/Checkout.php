<?php

declare(strict_types=1);

namespace UnzerPayment\Subscribers\Frontend;

use Doctrine\DBAL\Connection;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Enlight_View_Default;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Models\Shop\DetachedShop;
use UnzerPayment\Components\DependencyInjection\Factory\ViewBehavior\ViewBehaviorFactoryInterface;
use UnzerPayment\Components\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use UnzerPayment\Installers\Attributes;
use UnzerPayment\Installers\PaymentMethods;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use UnzerPayment\Services\PaymentIdentification\PaymentIdentificationServiceInterface;
use UnzerPayment\Services\PaymentVault\PaymentVaultServiceInterface;

class Checkout implements SubscriberInterface
{
    public const UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_SESSION_ID = 'unzer_payment_fraud_prevention_session_id';

    /** @var ContextServiceInterface */
    private $contextService;

    /** @var PaymentIdentificationServiceInterface */
    private $paymentIdentificationService;

    /** @var ViewBehaviorFactoryInterface */
    private $viewBehaviorFactory;

    /** @var PaymentVaultServiceInterface */
    private $paymentVaultService;

    /** @var ConfigReaderServiceInterface */
    private $configReaderService;

    /** @var Enlight_Components_Session_Namespace */
    private $sessionNamespace;

    /** @var Connection */
    private $connection;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $pluginDir;

    /** @var null|\Shopware\Models\Shop\DetachedShop */
    private $detachedShop;

    public function __construct(
        ContextServiceInterface $contextService,
        PaymentIdentificationServiceInterface $paymentIdentificationService,
        ViewBehaviorFactoryInterface $viewBehaviorFactory,
        PaymentVaultServiceInterface $paymentVaultService,
        ConfigReaderServiceInterface $configReaderService,
        Enlight_Components_Session_Namespace $sessionNamespace,
        Connection $connection,
        LoggerInterface $logger,
        string $pluginDir,
        ?DetachedShop $detachedShop
    ) {
        $this->contextService               = $contextService;
        $this->paymentIdentificationService = $paymentIdentificationService;
        $this->viewBehaviorFactory          = $viewBehaviorFactory;
        $this->paymentVaultService          = $paymentVaultService;
        $this->configReaderService          = $configReaderService;
        $this->sessionNamespace             = $sessionNamespace;
        $this->connection                   = $connection;
        $this->logger                       = $logger;
        $this->pluginDir                    = $pluginDir;
        $this->detachedShop                 = $detachedShop;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_controller_action_PreDispatch_Frontend_Checkout'        => 'onPreDispatchFinish',
            'Enlight_controller_action_PostDispatchSecure_Frontend_Checkout' => [
                ['onPostDispatchConfirm'],
                ['onPostDispatchFinish'],
                ['onPostDispatchShippingPayment'],
            ],
        ];
    }

    public function onPreDispatchFinish(ActionEventArgs $args): void
    {
        $request = $args->getRequest();

        if ($request->getActionName() !== 'finish') {
            return;
        }

        $uniqueId = $request->getParam('sUniqueID');

        if (empty($uniqueId)) {
            return;
        }

        $shopId = $this->detachedShop !== null ? $this->detachedShop->getId() : null;
        $userId = $this->sessionNamespace->offsetGet('sUserId');

        if ($userId !== null) {
            $userId = (int) $userId;
        }

        $orderNumber = $this->getOrderNumberByTemporaryId($uniqueId, $shopId, $userId);

        if ($orderNumber === '' && $shopId !== null) {
            $orderNumber = $this->getOrderNumberByTemporaryId($uniqueId, null, $userId);
        }

        $orderVariables                 = $this->sessionNamespace->offsetGet('sOrderVariables');
        $orderVariables['sOrderNumber'] = $orderNumber;
        $this->sessionNamespace->offsetSet('sOrderVariables', $orderVariables);
    }

    public function onPostDispatchConfirm(ActionEventArgs $args): void
    {
        $request = $args->getRequest();

        if ($request->getActionName() !== 'confirm') {
            return;
        }

        $view                  = $args->getSubject()->View();
        $selectedPaymentMethod = $this->getSelectedPayment();

        if (empty($selectedPaymentMethod)) {
            return;
        }

        if ($selectedPaymentMethod['name'] === PaymentMethods::PAYMENT_NAME_INSTALLMENT_SECURED) {
            $view->assign('unzerPaymentEffectiveInterest', (float) $this->configReaderService->get('effective_interest'));
        }

        $userData       = $view->getAssign('sUserData');
        $vaultedDevices = $this->paymentVaultService->getVaultedDevicesForCurrentUser($userData['billingaddress'], $userData['shippingaddress']);
        $locale         = str_replace('_', '-', $this->contextService->getShopContext()->getShop()->getLocale()->getLocale());

        if ($this->paymentIdentificationService->isUnzerPaymentWithFrame($selectedPaymentMethod)) {
            $view->assign('unzerPaymentFrame', $selectedPaymentMethod['attributes']['core']->get(Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME));
        }

        if ($this->paymentIdentificationService->isUnzerPaymentWithFraudPrevention($selectedPaymentMethod)) {
            $this->setFraudPreventionId($view);
        }

        $view->assign('unzerPaymentVault', $vaultedDevices);
        $view->assign('unzerPaymentLocale', $locale);
    }

    public function onPostDispatchFinish(ActionEventArgs $args): void
    {
        $request = $args->getRequest();

        if ($request->getActionName() !== 'finish') {
            return;
        }

        $selectedPayment = $this->getSelectedPayment();

        if (empty($selectedPayment)) {
            return;
        }

        $selectedPaymentName = $selectedPayment['name'];

        if (!$this->paymentIdentificationService->isUnzerPayment($selectedPayment)) {
            return;
        }

        $view = $args->getSubject()->View();

        if (!$view) {
            return;
        }

        $transactionId = $this->getUnzerPaymentId($view);

        if (empty($transactionId)) {
            return;
        }

        $viewHandlers         = $this->viewBehaviorFactory->getBehaviorHandler($selectedPaymentName);
        $behaviorTemplatePath = sprintf('%s/Resources/views/frontend/unzer_payment/behaviors/%s/finish.tpl', $this->pluginDir, $selectedPaymentName);
        $behaviorTemplate     = sprintf('frontend/unzer_payment/behaviors/%s/finish.tpl', $selectedPaymentName);

        /** @var ViewBehaviorHandlerInterface $behavior */
        foreach ($viewHandlers as $behavior) {
            $behavior->processCheckoutFinishBehavior($view, $transactionId);
        }

        if (file_exists($behaviorTemplatePath)) {
            $view->loadTemplate($behaviorTemplate);
        }

        $this->sessionNamespace->offsetUnset('unzerPaymentId');
    }

    public function onPostDispatchShippingPayment(ActionEventArgs $args): void
    {
        $request = $args->getRequest();

        if ($request->getActionName() !== 'shippingPayment') {
            return;
        }

        /** @var bool|string $unzerPaymentMessage */
        $unzerPaymentMessage = $request->get('unzerPaymentMessage', false);

        if (empty($unzerPaymentMessage) || $unzerPaymentMessage === false) {
            return;
        }

        $view       = $args->getSubject()->View();
        $messages   = (array) $view->getAssign('sErrorMessages');
        $messages[] = urldecode($unzerPaymentMessage);

        $view->assign('sErrorMessages', $messages);
    }

    private function getUnzerPaymentId(?Enlight_View_Default $view): string
    {
        $unzerPaymentId = null;

        if (!$view) {
            return '';
        }

        if ($this->sessionNamespace->offsetExists('unzerPaymentId')) {
            $unzerPaymentId = $this->sessionNamespace->offsetGet('unzerPaymentId');
        }

        if (!$unzerPaymentId) {
            $unzerPaymentId = $this->getPaymentIdByOrderNumber((string) $view->getAssign('sOrderNumber'));
        }

        if (!$unzerPaymentId) {
            $this->logger->warning(sprintf(
                'Could not find unzerPaymentId for order: %s',
                $view->getAssign('sOrderNumber')
            ));
        }

        return $unzerPaymentId ?: '';
    }

    private function getPaymentIdByOrderNumber(string $orderNumber): string
    {
        $transactionId = $this->connection->createQueryBuilder()
            ->select('transactionID')
            ->from('s_order')
            ->where('ordernumber = :orderNumber')
            ->setParameter('orderNumber', $orderNumber)
            ->execute()
            ->fetchColumn();

        return $transactionId ?: '';
    }

    private function getOrderNumberByTemporaryId(string $temporaryId, ?int $shopId, ?int $userId): string
    {
        $query = $this->connection->createQueryBuilder()
            ->select('ordernumber')
            ->from('s_order')
            ->where('temporaryID = :temporaryId')
            ->setParameter('temporaryId', $temporaryId);

        if ($shopId !== null) {
            // shopware saves the subShopId in the language column
            $query->andWhere('language = :subShopId')
                ->setParameter('subShopId', $shopId);
        }

        if ($userId !== null) {
            $query->andWhere('userID = :userId')
                ->setParameter('userId', $userId);
        }

        return $query
            ->execute()
            ->fetchColumn() ?: '';
    }

    private function getSelectedPayment(): ?array
    {
        $paymentMethod = $this->sessionNamespace->offsetGet('sOrderVariables')['sUserData']['additional']['payment'];

        if ($paymentMethod === false) {
            return null;
        }

        return $paymentMethod;
    }

    private function setFraudPreventionId(Enlight_View_Default $view): void
    {
        $fraudPreventionSessionId = $this->sessionNamespace->offsetGet(self::UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_SESSION_ID);

        if (empty($fraudPreventionSessionId)) {
            $fraudPreventionSessionId = Uuid::uuid4()->getHex()->toString();
            $this->sessionNamespace->offsetSet(self::UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_SESSION_ID, $fraudPreventionSessionId);
        }

        $view->assign('unzerPaymentFraudPreventionSessionId', $fraudPreventionSessionId ?? '');
    }
}
