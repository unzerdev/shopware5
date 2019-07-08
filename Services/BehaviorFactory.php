<?php
/**
 * Created by PhpStorm.
 * User: Heidelpay Sascha.Pflueger
 * Date: 08.07.2019
 */

namespace HeidelPayment\Services;

use HeidelPayment\Services\BehaviorHandler\BehaviorHandlerInterface;

class BehaviorFactory implements BehaviorFactoryInterface
{
    private $behaviorHandlers;

    public function getBehaviorHandler(string $paymentName)
    {

    }

    public function addBehaviorHandler(BehaviorHandlerInterface $behaviorHandler)
    {
        // TODO: Implement addBehaviorHandler() method.
    }
}