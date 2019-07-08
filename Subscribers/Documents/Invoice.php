<?php
namespace HeidelPayment\Subscribers\Documents;

use Enlight\Event\SubscriberInterface;
use HeidelPayment\Services\PaymentIdentificationServiceInterface;
use HeidelPayment\Services\ViewBehaviorFactoryInterface;
use HeidelPayment\Services\ViewBehaviorHandler\ViewBehaviorHandlerInterface;

class Invoice implements SubscriberInterface
{
    /**
     * @var PaymentIdentificationServiceInterface
     */
    private $paymentIdentificationService;
    /**
     * @var ViewBehaviorFactoryInterface
     */
    private $viewBehaviorFactory;

    public function __construct(PaymentIdentificationServiceInterface $paymentIdentificationService,ViewBehaviorFactoryInterface $viewBehaviorFactory)
    {
        $this->paymentIdentificationService = $paymentIdentificationService;

        $this->viewBehaviorFactory = $viewBehaviorFactory;
    }

    public static function getSubscribedEvents()
    {
        return ['Shopware_Components_Document::assignValues::after' => 'onRenderDocument'];
    }

    public function onRenderDocument(\Enlight_Hook_HookArgs $args)
    {
        /** @var \Shopware_Components_Document $subject */
        $subject = $args->getSubject();
        $view = $subject->_view;
        $orderData = $view->getTemplateVars('Order');
        $selectedPayment = $orderData['_payment'];
        $heidelPaymentId = $orderData['_order']["temporaryID"];
        $docType = $subject->_typID;

        if(
        !$this->paymentIdentificationService->isHeidelpayPayment($selectedPayment)|| empty($heidelPaymentId) || $docType !== "1"){
            return;
        }

        $behaviors = $this->viewBehaviorFactory->getBehaviorHandler($selectedPayment['name']);

        /** @var  ViewBehaviorHandlerInterface $behavior */
        foreach ($behaviors as $behavior){
            $behavior->handleInvoiceDocument($view,$heidelPaymentId);
        }


        echo "<pre>";
        var_dump($view->getVariable("bankData"));
        echo "</pre>";
        exit();

//        $return = $args->getReturn();

//        $args->setReturn($return);
    }

}