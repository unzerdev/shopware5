<?php

declare(strict_types=1);

namespace UnzerPayment\Subscribers\Frontend;

use Doctrine\DBAL\Connection;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Enlight_Controller_Request_RequestHttp;
use Enlight_View_Default;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Models\Shop\DetachedShop;
use Shopware_Components_Snippet_Manager;
use UnzerPayment\Components\CompanyTypes;
use UnzerPayment\Components\DependencyInjection\Factory\ViewBehavior\ViewBehaviorFactoryInterface;
use UnzerPayment\Components\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use UnzerPayment\Installers\Attributes;
use UnzerPayment\Installers\PaymentMethods;
use UnzerPayment\Services\PaymentIdentification\PaymentIdentificationServiceInterface;
use UnzerPayment\Services\PaymentVault\PaymentVaultServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientService;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\EmbeddedResources\CompanyInfo;

class Checkout implements SubscriberInterface
{
    public const UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_SESSION_ID = 'unzer_payment_fraud_prevention_session_id';
    public const UNZER_RESOURCE_ID                                   = 'unzerResourceId';

    private ContextServiceInterface $contextService;

    private PaymentIdentificationServiceInterface $paymentIdentificationService;

    private ViewBehaviorFactoryInterface $viewBehaviorFactory;

    private PaymentVaultServiceInterface $paymentVaultService;

    private UnzerPaymentClientServiceInterface $unzerPaymentClientService;

    private Enlight_Components_Session_Namespace $sessionNamespace;

    private Connection $connection;

    private LoggerInterface $logger;

    private string $pluginDir;

    private ?DetachedShop $detachedShop;

    private Shopware_Components_Snippet_Manager $snippetManager;

    public function __construct(
        ContextServiceInterface $contextService,
        PaymentIdentificationServiceInterface $paymentIdentificationService,
        ViewBehaviorFactoryInterface $viewBehaviorFactory,
        PaymentVaultServiceInterface $paymentVaultService,
        UnzerPaymentClientServiceInterface $unzerPaymentClientService,
        Enlight_Components_Session_Namespace $sessionNamespace,
        Connection $connection,
        LoggerInterface $logger,
        string $pluginDir,
        ?DetachedShop $detachedShop,
        Shopware_Components_Snippet_Manager $snippetManager
    ) {
        $this->contextService               = $contextService;
        $this->paymentIdentificationService = $paymentIdentificationService;
        $this->viewBehaviorFactory          = $viewBehaviorFactory;
        $this->paymentVaultService          = $paymentVaultService;
        $this->unzerPaymentClientService    = $unzerPaymentClientService;
        $this->sessionNamespace             = $sessionNamespace;
        $this->connection                   = $connection;
        $this->logger                       = $logger;
        $this->pluginDir                    = $pluginDir;
        $this->detachedShop                 = $detachedShop;
        $this->snippetManager               = $snippetManager;
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
                ['onPostDispatchPayment'],
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

        if (empty($selectedPaymentMethod) || !$this->paymentIdentificationService->isUnzerPayment($selectedPaymentMethod)) {
            return;
        }

        $userData = $view->getAssign('sUserData');

        if ($this->isRestrictedPaymentMethod($selectedPaymentMethod, $userData)) {
            $response     = $args->getResponse();
            $errorMessage = $this->snippetManager->getNamespace('frontend/unzer_payment/checkout/confirm')->get('restrictedPaymentMethod');
            $response->setRedirect($request->getBaseUrl() . '/checkout/shippingPayment?unzerPaymentMessage=' . urlencode($errorMessage));
        }

        $vaultedDevices  = $this->paymentVaultService->getVaultedDevicesForCurrentUser($userData['billingaddress'], $userData['shippingaddress']);
        $locale          = $this->getConvertedUnzerLocale();
        $publicKeyConfig = $this->getPublicKeyConfig($view, $selectedPaymentMethod['name']);

        $view->assign('unzerPaymentPublicKeyConfig', $publicKeyConfig);

        if ($this->paymentIdentificationService->isUnzerPaymentWithFrame($selectedPaymentMethod)) {
            if ($this->isB2bCustomer($userData)) {
                $keypairType = array_search($publicKeyConfig, UnzerPaymentClientService::PUBLIC_CONFIG_KEYS);
                $companyType = $this->getCompanyTypeByUserData($userData, $keypairType);

                if ($companyType) {
                    $view->assign('unzerPaymentCurrentCompanyType', $companyType);
                }
                $view->assign('unzerPaymentCompanyTypes', CompanyTypes::getConstants());
            }

            $view->assign('unzerPaymentFrame', $selectedPaymentMethod['attributes']['core']->get(Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME));
            $view->assign('unzerApplePaySelected', $selectedPaymentMethod['name'] === PaymentMethods::PAYMENT_NAME_APPLE_PAY);
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
        /** @var Enlight_Controller_Request_RequestHttp $request */
        $request = $args->getRequest();

        if ($request->getActionName() !== 'shippingPayment') {
            return;
        }

        $view = $args->getSubject()->View();
        $this->removeRestrictedPaymentMethods($view);

        /** @var bool|string $unzerPaymentMessage */
        $unzerPaymentMessage = $request->get('unzerPaymentMessage', false);

        if (empty($unzerPaymentMessage)) {
            return;
        }

        $messages   = (array) $view->getAssign('sErrorMessages');
        $messages[] = urldecode($unzerPaymentMessage);

        $view->assign('sErrorMessages', $messages);
    }

    public function onPostDispatchPayment(ActionEventArgs $args): void
    {
        $request = $args->getRequest();

        if ($request->getActionName() !== 'payment') {
            return;
        }

        $this->sessionNamespace->offsetSet(self::UNZER_RESOURCE_ID, $request->get(self::UNZER_RESOURCE_ID));
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

    private function getPublicKeyConfig(Enlight_View_Default $view, string $paymentName): string
    {
        $kaypairType = $this->unzerPaymentClientService->getKeypairType(
            $paymentName,
            $this->contextService->getShopContext()->getCurrency()->getCurrency(),
            $this->isB2bCustomer($view->getAssign('sUserData'))
        );

        return UnzerPaymentClientService::PUBLIC_CONFIG_KEYS[$kaypairType];
    }

    private function isB2bCustomer(array $userData): bool
    {
        return !empty($userData['billingaddress']['company']);
    }

    private function getExternalCustomerId(array $userData): string
    {
        return (string) $userData['additional']['user']['customernumber'];
    }

    private function getConvertedUnzerLocale(): string
    {
        return str_replace('_', '-', $this->contextService->getShopContext()->getShop()->getLocale()->getLocale());
    }

    private function getCompanyTypeByUserData(array $userData, ?string $keypairType = UnzerPaymentClientService::KEYPAIR_TYPE_GENERAL): ?string
    {
        $locale     = $this->getConvertedUnzerLocale();
        $customerId = $this->getExternalCustomerId($userData);
        $client     = $this->unzerPaymentClientService->getUnzerPaymentClientByType($keypairType, $locale, $this->contextService->getShopContext()->getShop()->getId());

        try {
            $customer = $client->fetchCustomerByExtCustomerId($customerId);

            if ($customer->getCompanyInfo() instanceof CompanyInfo) {
                return $customer->getCompanyInfo()->getCompanyType();
            }
        } catch (UnzerApiException $apiException) {
            // Customer not found. No need to handle this exception here.
        }

        return null;
    }

    // TODO If more payment methods with restrictions are added in the future, this should be separated into a separate classes
    // An idea would be a PaymentMethodRestrictionIterator which iterates over all restrictions and removes the corresponding payment methods
    private function removeRestrictedPaymentMethods(Enlight_View_Default $view): void
    {
        $paymentMethods = $view->getAssign('sPayments');
        $countryIso     = $view->getAssign('sUserData')['additional']['country']['countryiso'] ?? '';
        $currency       = $this->contextService->getShopContext()->getCurrency()->getCurrency();

        foreach ($paymentMethods as $key => $paymentMethod) {
            if ($paymentMethod['name'] === PaymentMethods::PAYMENT_NAME_PAYLATER_INSTALLMENT) {
                if ($currency !== 'EUR' && $currency !== 'CHF') {
                    unset($paymentMethods[$key]);

                    continue;
                }

                if ($countryIso !== 'DE' && $countryIso !== 'AT' && $countryIso !== 'CH') {
                    unset($paymentMethods[$key]);
                }
            }
        }

        $view->assign('sPayments', $paymentMethods);
    }

    private function isRestrictedPaymentMethod(array $selectedPaymentMethod, $userData): bool
    {
        $countryIso = $userData['additional']['country']['countryiso'] ?? '';
        $currency   = $this->contextService->getShopContext()->getCurrency()->getCurrency();

        if ($selectedPaymentMethod['name'] === PaymentMethods::PAYMENT_NAME_PAYLATER_INSTALLMENT) {
            if ($currency !== 'EUR' && $currency !== 'CHF') {
                return true;
            }

            if ($countryIso !== 'DE' && $countryIso !== 'AT' && $countryIso !== 'CH') {
                return true;
            }
        }

        return false;
    }
}
