<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentHandler\Traits;

use RuntimeException;
use UnzerPayment\Services\UnzerOrderComment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;

/**
 * @property UnzerOrderComment    $unzerOrderComment
 * @property Authorization|Charge $paymentResult
 */
trait OrderComment
{
    public function setOrderComment(string $namespace): void
    {
        if (!($this->paymentResult instanceof Charge || $this->paymentResult instanceof Authorization)) {
            throw new RuntimeException('Can not set order comment without an authorization or charge');
        }

        $this->unzerOrderComment->setOrderCommentBeforeSavingOrder($this->paymentResult, $namespace);
    }
}
