<?php

declare(strict_types=1);

namespace UnzerPayment\Subscribers\Core;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs as EventArgs;
use UnzerPayment\Components\DependencyInjection\Factory\ViewBehavior\ViewBehaviorFactoryInterface;
use UnzerPayment\Components\ViewBehaviorHandler\ViewBehaviorHandlerInterface;
use UnzerPayment\Services\PaymentIdentification\PaymentIdentificationServiceInterface;

class Mail implements SubscriberInterface
{
    /** @var PaymentIdentificationServiceInterface */
    private $identificationService;

    /** @var ViewBehaviorFactoryInterface */
    private $behaviorFactory;

    public function __construct(PaymentIdentificationServiceInterface $identificationService, ViewBehaviorFactoryInterface $behaviorFactory)
    {
        $this->identificationService = $identificationService;
        $this->behaviorFactory       = $behaviorFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Modules_Order_SendMail_FilterVariables' => 'onFilterMailVariables',
        ];
    }

    public function onFilterMailVariables(EventArgs $args): void
    {
        $variables = $args->getReturn();

        $paymentMethod = $variables['additional']['payment'];

        if (!$this->identificationService->isHeidelpayPayment($paymentMethod)) {
            return;
        }

        $heidelPaymentId     = $variables['sBookingID'];
        $additionalVariables = [];

        $viewHandlers = $this->behaviorFactory->getBehaviorHandler($paymentMethod['name']);

        /** @var ViewBehaviorHandlerInterface $behavior */
        foreach ($viewHandlers as $behavior) {
            $behaviorResult      = $behavior->processEmailVariablesBehavior($heidelPaymentId);
            $additionalVariables = array_merge($additionalVariables, $behaviorResult);
        }

        $variables['additional']['heidelpay'] = $additionalVariables;

        $args->setReturn($variables);
    }
}
