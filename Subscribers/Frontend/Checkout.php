<?php

declare(strict_types=1);

namespace UnzerPayment\Subscribers\Frontend;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;
use Enlight_View_Default;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use UnzerPayment\Components\DependencyInjection\Factory\ViewBehavior\ViewBehaviorFactoryInterface;
use UnzerPayment\Components\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use UnzerPayment\Installers\Attributes;
use UnzerPayment\Installers\PaymentMethods;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use UnzerPayment\Services\DependencyProvider\DependencyProviderServiceInterface;
use UnzerPayment\Services\PaymentIdentification\PaymentIdentificationServiceInterface;
use UnzerPayment\Services\PaymentVault\PaymentVaultServiceInterface;

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

        if ($selectedPaymentMethod['name'] === PaymentMethods::PAYMENT_NAME_INSTALLMENT_SECURED) {
            $view->assign('unzerPaymentEffectiveInterest', (float) $this->configReaderService->get('effective_interest'));
        }

        $userData       = $view->getAssign('sUserData');
        $vaultedDevices = $this->paymentVaultService->getVaultedDevicesForCurrentUser($userData['billingaddress'], $userData['shippingaddress']);
        $locale         = str_replace('_', '-', $this->contextService->getShopContext()->getShop()->getLocale()->getLocale());

        if ($this->paymentIdentificationService->isUnzerPaymentWithFrame($selectedPaymentMethod)) {
            $view->assign('unzerPaymentFrame', $selectedPaymentMethod['attributes']['core']->get(Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME));
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

        $session         = $this->dependencyProvider->getSession();
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

        $transactionId = $this->getUnzerPaymentId($session, $view);

        if (empty($transactionId)) {
            return;
        }

        $viewHandlers         = $this->viewBehaviorFactory->getBehaviorHandler($selectedPaymentName);
        $behaviorTemplatePath = sprintf('%s/Resources/views/frontend/unzerPayment/behaviors/%s/finish.tpl', $this->pluginDir, $selectedPaymentName);
        $behaviorTemplate     = sprintf('frontend/unzerPayment/behaviors/%s/finish.tpl', $selectedPaymentName);

        /** @var ViewBehaviorHandlerInterface $behavior */
        foreach ($viewHandlers as $behavior) {
            $behavior->processCheckoutFinishBehavior($view, $transactionId);
        }

        if (file_exists($behaviorTemplatePath)) {
            $view->loadTemplate($behaviorTemplate);
        }

        $session->offsetUnset('unzerPaymentId');
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

    private function getUnzerPaymentId(?Enlight_Components_Session_Namespace $session, ?Enlight_View_Default $view): string
    {
        $unzerPaymentId = null;

        if (!$session || !$view) {
            return '';
        }

        if ($session->offsetExists('unzerPaymentId')) {
            $unzerPaymentId = $session->offsetGet('unzerPaymentId');
        }

        if (!$unzerPaymentId) {
            $unzerPaymentId = $this->getPaymentIdByOrderNumber((string) $view->getAssign('sOrderNumber'));
        }

        if (!$unzerPaymentId) {
            $this->dependencyProvider->get('unzer_payment.logger')
                ->warning(sprintf('Could not find unzerPaymentId for order: %s', $view->getAssign('sOrderNumber')));
        }

        return $unzerPaymentId ?: '';
    }

    private function getPaymentIdByOrderNumber(string $orderNumber): string
    {
        /** @var Connection $connection */
        $connection = $this->dependencyProvider->get('dbal_connection');

        if ($connection) {
            /** @var Statement $driverStatement */
            $driverStatement = $connection->createQueryBuilder()
                ->select('transactionID')
                ->from('s_order')
                ->where('ordernumber = :orderNumber')
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
