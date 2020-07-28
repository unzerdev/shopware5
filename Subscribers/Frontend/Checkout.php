<?php

declare(strict_types=1);

namespace HeidelPayment\Subscribers\Frontend;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Enlight_View_Default;
use HeidelPayment\Components\DependencyInjection\Factory\ViewBehavior\ViewBehaviorFactoryInterface;
use HeidelPayment\Components\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use HeidelPayment\Installers\Attributes;
use HeidelPayment\Installers\PaymentMethods;
use HeidelPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use HeidelPayment\Services\DependencyProvider\DependencyProviderServiceInterface;
use HeidelPayment\Services\PaymentIdentification\PaymentIdentificationServiceInterface;
use HeidelPayment\Services\PaymentVault\PaymentVaultServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;

class Checkout implements SubscriberInterface
{
    /** @var ContextServiceInterface */
    private $contextService;

    /** @var PaymentIdentificationServiceInterface */
    private $paymentIdentificationService;

    /** @var DependencyProviderServiceInterface */
    private $dependencyProvider;

    /** @var ViewBehaviorFactoryInterface */
    private $viewBehaviorFactory;

    /** @var PaymentVaultServiceInterface */
    private $paymentVaultService;

    /** @var ConfigReaderServiceInterface */
    private $configReaderService;

    /** @var string */
    private $pluginDir;

    public function __construct(
        ContextServiceInterface $contextService,
        PaymentIdentificationServiceInterface $paymentIdentificationService,
        DependencyProviderServiceInterface $dependencyProvider,
        ViewBehaviorFactoryInterface $viewBehaviorFactory,
        PaymentVaultServiceInterface $paymentVaultService,
        ConfigReaderServiceInterface $configReaderService,
        string $pluginDir
    ) {
        $this->contextService               = $contextService;
        $this->paymentIdentificationService = $paymentIdentificationService;
        $this->dependencyProvider           = $dependencyProvider;
        $this->viewBehaviorFactory          = $viewBehaviorFactory;
        $this->paymentVaultService          = $paymentVaultService;
        $this->configReaderService          = $configReaderService;
        $this->pluginDir                    = $pluginDir;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_controller_action_PostDispatchSecure_Frontend_Checkout' => [
                ['onPostDispatchConfirm'],
                ['onPostDispatchFinish'],
                ['onPostDispatchShippingPayment'],
            ],
        ];
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

        if ($selectedPaymentMethod['name'] === PaymentMethods::PAYMENT_NAME_HIRE_PURCHASE) {
            $view->assign('heidelpayEffectiveInterest', (float) $this->configReaderService->get('effective_interest'));
        }

        $userData       = $view->getAssign('sUserData');
        $vaultedDevices = $this->paymentVaultService->getVaultedDevicesForCurrentUser($userData['billingaddress'], $userData['shippingaddress']);
        $locale         = str_replace('_', '-', $this->contextService->getShopContext()->getShop()->getLocale()->getLocale());

        if ($this->paymentIdentificationService->isHeidelpayPaymentWithFrame($selectedPaymentMethod)) {
            $view->assign('heidelpayFrame', $selectedPaymentMethod['attributes']['core']->get(Attributes::HEIDEL_ATTRIBUTE_PAYMENT_FRAME));
        }
        $view->assign('heidelpayVault', $vaultedDevices);
        $view->assign('heidelpayLocale', $locale);
    }

    public function onPostDispatchFinish(ActionEventArgs $args): void
    {
        $request = $args->getRequest();

        if ($request->getActionName() !== 'finish') {
            return;
        }

        $session         = $this->dependencyProvider->getSession();
        $selectedPayment = $this->getSelectedPayment();

        if (empty($selectedPayment)) {
            return;
        }

        $selectedPaymentName = $selectedPayment['name'];

        if (!$this->paymentIdentificationService->isHeidelpayPayment($selectedPayment)) {
            return;
        }

        $view = $args->getSubject()->View();

        if (!$view) {
            return;
        }

        $heidelPaymentId = $this->getHeidelPaymentId($session, $view);

        if (empty($heidelPaymentId)) {
            return;
        }

        $viewHandlers         = $this->viewBehaviorFactory->getBehaviorHandler($selectedPaymentName);
        $behaviorTemplatePath = sprintf('%s/Resources/views/frontend/heidelpay/behaviors/%s/finish.tpl', $this->pluginDir, $selectedPaymentName);
        $behaviorTemplate     = sprintf('frontend/heidelpay/behaviors/%s/finish.tpl', $selectedPaymentName);

        /** @var ViewBehaviorHandlerInterface $behavior */
        foreach ($viewHandlers as $behavior) {
            $behavior->processCheckoutFinishBehavior($view, $heidelPaymentId);
        }

        if (file_exists($behaviorTemplatePath)) {
            $view->loadTemplate($behaviorTemplate);
        }

        $session->offsetUnset('heidelPaymentId');
    }

    public function onPostDispatchShippingPayment(ActionEventArgs $args): void
    {
        $request = $args->getRequest();

        if ($request->getActionName() !== 'shippingPayment') {
            return;
        }

        /** @var bool|string $heidelpayMessage */
        $heidelpayMessage = $request->get('heidelpayMessage', false);

        if (empty($heidelpayMessage) || $heidelpayMessage === false) {
            return;
        }

        $view       = $args->getSubject()->View();
        $messages   = (array) $view->getAssign('sErrorMessages');
        $messages[] = urldecode($heidelpayMessage);

        $view->assign('sErrorMessages', $messages);
    }

    private function getHeidelPaymentId(?Enlight_Components_Session_Namespace $session, ?Enlight_View_Default $view): string
    {
        if (!$session || !$view) {
            return '';
        }

        if ($session->offsetExists('heidelPaymentId')) {
            $heidelPaymentId = $session->offsetGet('heidelPaymentId');
        }

        if (!$heidelPaymentId) {
            $heidelPaymentId = $this->getPaymentIdByOrderNumber((string) $view->getAssign('sOrderNumber'));
        }

        if (!$heidelPaymentId) {
            $this->dependencyProvider->get('heidel_payment.logger')
                ->warning(sprintf('Could not find heidelPaymentId for order: %s', $view->getAssign('sOrderNumber')));
        }

        return $heidelPaymentId ?: '';
    }

    private function getPaymentIdByOrderNumber(string $orderNumber): string
    {
        /** @var Connection $connection */
        $connection = $this->dependencyProvider->get('dbal_connection');

        if ($connection) {
            /** @var Statement $driverStatement */
            $driverStatement = $connection->createQueryBuilder()
                ->select(sprintf('soa.%s', Attributes::HEIDEL_ATTRIBUTE_TRANSACTION_ID))
                ->from('s_order', 'so')
                ->innerJoin('so', 's_order_attributes', 'soa', 'soa.orderID = so.id')
                ->where('so.ordernumber = :orderNumber')
                ->setParameter('orderNumber', $orderNumber)
                ->execute();

            $transactionId = $driverStatement->fetchColumn();
        }

        return $transactionId ?: '';
    }

    private function getSelectedPayment(): ?array
    {
        return $this->dependencyProvider->getSession()->offsetGet('sOrderVariables')['sUserData']['additional']['payment'];
    }
}
