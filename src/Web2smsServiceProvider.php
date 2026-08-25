<?php

namespace ITPalert\Web2sms;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use ITPalert\Web2sms\Contracts\Client as ClientContract;
use ITPalert\Web2sms\Testing\Web2smsFake;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Web2smsServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ClientContract::class, function ($app) {
            $config = $app['config']['services.web2sms'] ?? [];

            return match ($this->driver($app, $config)) {
                // Records and swallows. `log` is the same fake with somewhere
                // to write what it swallowed, which is what makes it useful
                // during local development.
                'fake', 'array', 'null' => new Web2smsFake(),
                'log' => new Web2smsFake($app->make(LoggerInterface::class)),
                default => $this->apiClient($app, $config),
            };
        });

        // Deliberately no alias from the concrete Client back to the contract.
        // An alias is resolved before bindings are looked up, so it silently
        // swallows any later `bind(Client::class, ...)` — which is exactly how
        // a test that injects a mock HTTP client ends up talking to the real
        // API instead. Callers bind and type-hint the contract.
    }

    /**
     * Which client to build.
     *
     * Defaults to the fake under the testing environment, deliberately. Every
     * other outbound integration is switched off in tests by a setting someone
     * has to remember to add, and a text message is the one kind of outbound
     * that costs money per attempt and arrives on a stranger's phone. Sending
     * for real during tests is still possible, by naming the driver.
     *
     * @param  array<string, mixed>  $config
     */
    protected function driver(Application $app, array $config): string
    {
        if ($driver = $config['driver'] ?? null) {
            return (string) $driver;
        }

        return $app->environment('testing') ? 'fake' : 'api';
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function apiClient(Application $app, array $config): Client
    {
        if (empty($config)) {
            throw new RuntimeException(
                'Web2sms configuration not found. Please add credentials to config/services.php'
            );
        }

        $httpClient = null;

        if ($httpClientConfig = $config['http_client'] ?? null) {
            $httpClient = $app->make($httpClientConfig);
        }

        return Web2sms::make($config, $httpClient)->client();
    }
}
