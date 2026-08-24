<?php

namespace Tiash\LaravelBkash;

use Illuminate\Support\ServiceProvider;
use Tiash\LaravelBkash\Api\AgreementApi;
use Tiash\LaravelBkash\Api\PayoutApi;
use Tiash\LaravelBkash\Api\RefundApi;
use Tiash\LaravelBkash\Api\TokenizedPaymentApi;
use Tiash\LaravelBkash\Auth\Credentials;
use Tiash\LaravelBkash\Auth\TokenCache;
use Tiash\LaravelBkash\Auth\TokenManager;
use Tiash\LaravelBkash\Contracts\BkashClientInterface;
use Tiash\LaravelBkash\Http\GuzzleClient;
use Tiash\LaravelBkash\Http\ResponseNormalizer;
use Tiash\LaravelBkash\Security\SnsCertificateStore;
use Tiash\LaravelBkash\Security\SnsSignatureVerifier;
use Tiash\LaravelBkash\Support\IdempotencyLock;

class BkashServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/bkash.php' => config_path('bkash.php'),
        ], 'bkash-config');

        $this->publishes([
            __DIR__ . '/routes/bkash.php' => base_path('routes/bkash.php'),
        ], 'bkash-routes');

        $this->publishes([
            __DIR__ . '/Views' => resource_path('views/vendor/bkash'),
        ], 'bkash-views');

        $this->loadViewsFrom(__DIR__ . '/Views', 'bkash');

        $this->loadRoutesFrom(__DIR__ . '/routes/bkash.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Tiash\LaravelBkash\Console\InstallCommand::class,
                \Tiash\LaravelBkash\Console\TestSandboxCommand::class,
            ]);
        }

        $this->validateConfig();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/bkash.php', 'bkash');

        $baseUrl = fn ($app) => $app['config']['bkash']['sandbox']
            ? "https://tokenized.sandbox.bka.sh/{$app['config']['bkash']['api_version']}/tokenized"
            : "https://tokenized.pay.bka.sh/{$app['config']['bkash']['api_version']}/tokenized";

        $this->app->singleton(ResponseNormalizer::class);

        $this->app->singleton(BkashClientInterface::class, function ($app) use ($baseUrl) {
            return new GuzzleClient($baseUrl($app), $app['config']['bkash']['http'], $app[ResponseNormalizer::class]);
        });

        $this->app->singleton(Credentials::class, function ($app) {
            return new Credentials($app['config']['bkash']['accounts']);
        });

        $this->app->singleton(TokenCache::class, function ($app) {
            $cacheStore = $app['config']['bkash']['cache']['store'] ?? null;
            $cache = $cacheStore ? $app['cache']->store($cacheStore) : $app['cache']->driver();
            $ttlBuffer = $app['config']['bkash']['cache']['ttl_buffer'] ?? 300;
            return new TokenCache($cache, $ttlBuffer);
        });

        $this->app->singleton(TokenManager::class, function ($app) use ($baseUrl) {
            return new TokenManager(
                $app[BkashClientInterface::class],
                $app[TokenCache::class],
                $app[Credentials::class],
                $baseUrl($app)
            );
        });

        $this->app->singleton(IdempotencyLock::class, function ($app) {
            $cacheStore = $app['config']['bkash']['cache']['store'] ?? null;
            $cache = $cacheStore ? $app['cache']->store($cacheStore) : $app['cache']->driver();
            return new IdempotencyLock($cache);
        });

        $this->app->singleton(SnsCertificateStore::class, function ($app) {
            $cacheStore = $app['config']['bkash']['cache']['store'] ?? null;
            $cache = $cacheStore ? $app['cache']->store($cacheStore) : $app['cache']->driver();
            return new SnsCertificateStore($cache);
        });

        $this->app->singleton(SnsSignatureVerifier::class, function ($app) {
            $cacheStore = $app['config']['bkash']['cache']['store'] ?? null;
            $cache = $cacheStore ? $app['cache']->store($cacheStore) : $app['cache']->driver();
            return new SnsSignatureVerifier(
                $app[SnsCertificateStore::class],
                $cache,
            );
        });

        $this->app->singleton(TokenizedPaymentApi::class, function ($app) use ($baseUrl) {
            return new TokenizedPaymentApi(
                $app[BkashClientInterface::class],
                $app[TokenManager::class],
                $app[Credentials::class],
                $app[IdempotencyLock::class],
                $baseUrl($app),
                $app['config']['bkash']['callback_url'] ?? ''
            );
        });

        $this->app->singleton(RefundApi::class, function ($app) use ($baseUrl) {
            return new RefundApi(
                $app[BkashClientInterface::class],
                $app[TokenManager::class],
                $app[Credentials::class],
                $baseUrl($app)
            );
        });

        $this->app->singleton(AgreementApi::class, function ($app) use ($baseUrl) {
            return new AgreementApi(
                $app[BkashClientInterface::class],
                $app[TokenManager::class],
                $app[Credentials::class],
                $baseUrl($app),
                $app['config']['bkash']['callback_url'] ?? ''
            );
        });

        $this->app->singleton(PayoutApi::class, function ($app) use ($baseUrl) {
            return new PayoutApi(
                $app[BkashClientInterface::class],
                $app[TokenManager::class],
                $app[Credentials::class],
                $baseUrl($app)
            );
        });

        $this->app->singleton(BkashManager::class);

        $this->app->alias(BkashManager::class, 'bkash');
        $this->app->alias(TokenizedPaymentApi::class, 'bkash.payment');
        $this->app->alias(RefundApi::class, 'bkash.refund');
        $this->app->alias(AgreementApi::class, 'bkash.agreement');
        $this->app->alias(PayoutApi::class, 'bkash.payout');
        $this->app->alias(BkashClientInterface::class, 'bkash.client');
    }

    private function validateConfig(): void
    {
        if ($this->app->environment('production')) {
            $accounts = config('bkash.accounts', []);
            foreach ($accounts as $name => $account) {
                $required = ['app_key', 'app_secret', 'username', 'password'];
                foreach ($required as $key) {
                    if (empty($account[$key])) {
                        throw new \RuntimeException("bKash config missing: accounts.{$name}.{$key}");
                    }
                }
            }

            if (empty(config('bkash.callback_url'))) {
                throw new \RuntimeException('bKash config missing: callback_url');
            }
        }
    }
}