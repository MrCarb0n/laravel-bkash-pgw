<?php

namespace Tiash\LaravelBkash;

use Tiash\LaravelBkash\Api\AgreementApi;
use Tiash\LaravelBkash\Api\PayoutApi;
use Tiash\LaravelBkash\Api\RefundApi;
use Tiash\LaravelBkash\Api\TokenizedPaymentApi;

class BkashManager
{
    private $payment;
    private $refund;
    private $agreement;
    private $payout;

    public function __construct(
        TokenizedPaymentApi $payment,
        RefundApi $refund,
        AgreementApi $agreement,
        PayoutApi $payout
    ) {
        $this->payment   = $payment;
        $this->refund    = $refund;
        $this->agreement = $agreement;
        $this->payout    = $payout;
    }

    /** Tokenized checkout operations: create, execute, query, search. */
    public function payment(): TokenizedPaymentApi
    {
        return $this->payment;
    }

    /** Refund operations: refund, status. */
    public function refund(): RefundApi
    {
        return $this->refund;
    }

    /** Agreement (mandate) operations: create, execute, status. */
    public function agreement(): AgreementApi
    {
        return $this->agreement;
    }

    /** B2C payout operations: disburse. */
    public function payout(): PayoutApi
    {
        return $this->payout;
    }
}