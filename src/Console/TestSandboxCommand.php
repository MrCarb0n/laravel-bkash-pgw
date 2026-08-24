<?php

namespace Tiash\LaravelBkash\Console;

use Illuminate\Console\Command;
use Throwable;
use Tiash\LaravelBkash\Api\TokenizedPaymentApi;
use Tiash\LaravelBkash\Auth\TokenManager;

class TestSandboxCommand extends Command
{
    protected $signature = 'bkash:test-sandbox {--account=default : bKash account name to test}';
    protected $description = 'Run a live smoke test against the bKash sandbox (token, create, query)';

    public function handle(TokenManager $tokens, TokenizedPaymentApi $payments): int
    {
        $account = $this->option('account');

        if (!config('bkash.sandbox', true)) {
            $this->error('BKASH_SANDBOX is false — refusing to run a smoke test against production.');

            return self::FAILURE;
        }

        try {
            $this->line('1/3 Granting token...');
            $token = $tokens->forceRefresh($account);
            $this->info('   OK (' . substr($token, 0, 12) . '...)');

            $this->line('2/3 Creating payment...');
            $payment = $payments->create([
                'payerReference'        => 'bkash-test-sandbox',
                'amount'                => 1,
                'merchantInvoiceNumber' => 'TEST-' . uniqid(),
            ], $account);

            if (empty($payment['paymentID'])) {
                throw new \RuntimeException('create returned no paymentID: ' . json_encode($payment));
            }
            $this->info('   OK (paymentID ' . $payment['paymentID'] . ')');

            $this->line('3/3 Querying payment status...');
            $status = $payments->query($payment['paymentID'], $account);
            $this->info('   OK (status: ' . ($status['transactionStatus'] ?? $status['paymentStatus'] ?? 'unknown') . ')');
        } catch (Throwable $e) {
            $this->error('FAILED: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✅ Sandbox connectivity verified. Complete the flow by paying at:');
        $this->line('   ' . ($payment['bkashURL'] ?? '(no bkashURL in response)'));

        return self::SUCCESS;
    }
}
