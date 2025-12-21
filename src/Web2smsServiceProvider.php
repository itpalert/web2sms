<?php

namespace ITPalert\Web2sms;

use Illuminate\Support\ServiceProvider;
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
        // Bind the Client class (not Web2sms)
        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']['services.web2sms'] ?? [];

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
        });
    }
}