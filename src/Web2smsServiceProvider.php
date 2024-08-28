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
        $this->app->singleton(Web2sms::class, function ($app) {
            $config = $app['config']['services.web2sms'];

            $httpClient = null;

            if ($httpClient = $config['http_client'] ?? null) {
                $httpClient = $app->make($httpClient);
            } elseif (! class_exists('GuzzleHttp\Client')) {
                throw new RuntimeException(
                    'The Web2sms client requires a "psr/http-client-implementation" class such as Guzzle.'
                );
            }

            return Web2sms::make($config, $httpClient)->client();
        });
    }
}