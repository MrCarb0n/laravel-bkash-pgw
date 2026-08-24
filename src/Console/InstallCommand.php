<?php

namespace Tiash\LaravelBkash\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'bkash:install';
    protected $description = 'Publish bKash config and show onboarding checklist';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'bkash-config',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'bkash-routes',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'bkash-views',
            '--force' => true,
        ]);

        $this->info('bKash config, routes, and views published.');
        $this->newLine();

        $this->info('✅ Onboarding Checklist:');
        $this->line('  1. Edit config/bkash.php with your bKash credentials');
        $this->line('  2. Set BKASH_SANDBOX=true in .env for testing');
        $this->line('  3. Add sandbox credentials from developer.bka.sh:');
        $this->line('     - BKASH_APP_KEY');
        $this->line('     - BKASH_APP_SECRET');
        $this->line('     - BKASH_USERNAME');
        $this->line('     - BKASH_PASSWORD');
        $this->line('  4. Set BKASH_CALLBACK_URL to your public HTTPS endpoint');
        $this->line('  5. For production: IP whitelist your server with bKash');
        $this->line('  6. Run: php artisan bkash:test-sandbox to verify connectivity');
        $this->newLine();

        $this->warn('⚠️  Production requires:');
        $this->line('  - Static public IP whitelisted with bKash');
        $this->line('  - Public HTTPS callback URL (no localhost)');
        $this->line('  - Production credentials (different from sandbox)');

        return self::SUCCESS;
    }
}