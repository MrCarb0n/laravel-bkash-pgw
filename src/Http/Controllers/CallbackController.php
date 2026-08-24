<?php

namespace Tiash\LaravelBkash\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Tiash\LaravelBkash\Api\TokenizedPaymentApi;
use Tiash\LaravelBkash\Events\PaymentCompleted;
use Tiash\LaravelBkash\Events\PaymentFailed;

class CallbackController extends Controller
{
    private $paymentApi;

    public function __construct(TokenizedPaymentApi $paymentApi)
    {
        $this->paymentApi = $paymentApi;
    }

    /** Handle bKash redirect callback: execute payment, dispatch events.
     *  ponytail: no signature check — bKash's callback signing scheme is undocumented.
     *  The execute API is the verification: server-to-server, single-use per paymentID,
     *  only completes for a genuinely paid payment. If bKash ever documents the scheme,
     *  re-add validation in front of execute(). */
    public function handle(Request $request)
    {
        $status    = $request->query('status');
        $paymentId = $request->query('paymentID');
        $account   = $request->query('account', 'default');

        if (!$paymentId) {
            return $this->failure('Missing paymentID');
        }

        if ($status !== 'success') {
            return $this->failure($status === 'cancel' ? 'Payment cancelled' : 'Payment failed');
        }

        try {
            $response = $this->paymentApi->execute($paymentId, $account);
        } catch (\Throwable $e) {
            return $this->failure($e->getMessage());
        }

        if (($response['transactionStatus'] ?? '') === 'Completed') {
            event(new PaymentCompleted($response));
            return $this->success('Payment successful', $response['trxID'] ?? null);
        }

        event(new PaymentFailed($response));
        return $this->failure($response['statusMessage'] ?? 'Payment execution failed');
    }

    private function success(string $message, ?string $trxId = null)
    {
        return view('bkash::success', compact('message', 'trxId'));
    }

    private function failure(string $message, ?string $trxId = null)
    {
        return view('bkash::failed', compact('message', 'trxId'));
    }
}