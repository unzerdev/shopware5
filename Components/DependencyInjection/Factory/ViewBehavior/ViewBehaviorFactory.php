<?php

declare(strict_types=1);

namespace UnzerPayment\Components\DependencyInjection\Factory\ViewBehavior;

use UnzerPayment\Components\ViewBehaviorHandler\ViewBehaviorHandlerInterface;

class ViewBehaviorFactory implements ViewBehaviorFactoryInterface
{
    /** @var ViewBehaviorHandlerInterface[][] */
    protected $viewBehaviorHandler;

    public function getBehaviorHandler(string $paymentName): array
    {
        if (!array_key_exists($paymentName, $this->viewBehaviorHandler)) {
            return [];
        }

        return $this->viewBehaviorHandler[$paymentName];
    }

    public function getDocumentSupportedBehaviorHandler(string $paymentName, int $documentType): array
    {
        if (!array_key_exists($paymentName, $this->viewBehaviorHandler)) {
            return [];
        }

        return array_filter($this->viewBehaviorHandler[$paymentName], static function (ViewBehaviorHandlerInterface $behaviorHandler) use ($documentType) {
            return $behaviorHandler->supportDocumentBehavior($documentType);
        });
    }

    public function addBehaviorHandler(ViewBehaviorHandlerInterface $viewBehaviorHandler, string $paymentName): void
    {
        $this->viewBehaviorHandler[$paymentName][] = $viewBehaviorHandler;
    }
}
