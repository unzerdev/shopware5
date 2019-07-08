<?php
namespace HeidelPayment\Services\BehaviorHandler;

interface BehaviorHandlerInterface
{
    public function execute(\Enlight_Controller_Action $controller);
}