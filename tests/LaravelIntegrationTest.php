<?php

namespace ITPalert\Web2sms\Tests;

use Orchestra\Testbench\TestCase;
use ITPalert\Web2sms\Web2sms;
use ITPalert\Web2sms\Web2smsServiceProvider;
use ITPalert\Web2sms\Client;
use ITPalert\Web2sms\Facades\Web2sms as Web2smsFacade;

class LaravelIntegrationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [Web2smsServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Web2sms' => Web2smsFacade::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('services.web2sms', [
            'key' => 'test_api_key',
            'secret' => 'test_api_secret',
            'sms_from' => 'TEST',
            'account_type' => 'prepaid',
        ]);
    }

    public function test_service_provider_registers_singleton()
    {
        $instance1 = $this->app->make(Web2sms::class);
        $instance2 = $this->app->make(Web2sms::class);

        $this->assertInstanceOf(Client::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    public function test_service_provider_loads_config_from_services()
    {
        $client = $this->app->make(Web2sms::class);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('https://www.web2sms.ro', $client->apiUrl);
        $this->assertEquals('/prepaid/message', $client->selectedEndpointURL);
    }

    public function test_service_provider_respects_account_type_postpaid()
    {
        $this->app['config']->set('services.web2sms.account_type', 'postpaid');

        // Force rebind
        $this->app->forgetInstance(Web2sms::class);

        $client = $this->app->make(Web2sms::class);

        $this->assertEquals('/send/message', $client->selectedEndpointURL);
    }

    public function test_service_provider_throws_exception_when_config_missing()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Web2sms configuration not found');

        $app = $this->createApplication();
        $app['config']->set('services.web2sms', []);

        $provider = new Web2smsServiceProvider($app);
        $provider->register();

        $app->make(Web2sms::class);
    }

    public function test_facade_resolves_to_client()
    {
        $client = Web2smsFacade::getFacadeRoot();

        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_facade_can_call_client_methods()
    {
        // Mock the HTTP client to avoid actual API calls
        $mockHttpClient = $this->createMock(\GuzzleHttp\Client::class);
        
        $stream = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode([
            'id' => 'test_id',
            'error' => ['code' => 0, 'message' => 'OK']
        ]));
        $stream->method('rewind');

        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(201);
        $response->method('getBody')->willReturn($stream);

        $mockHttpClient->method('post')->willReturn($response);

        // Replace the bound instance with one using our mock
        $this->app->singleton(Web2sms::class, function ($app) use ($mockHttpClient) {
            $config = $app['config']['services.web2sms'];
            return Web2sms::make($config, $mockHttpClient)->client();
        });

        $sms = new \ITPalert\Web2sms\SMS('0712345678', 'TEST', 'Test message', 'text');
        $response = Web2smsFacade::send($sms);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('test_id', $response->getMessageId());
    }

    public function test_service_provider_uses_custom_http_client_from_config()
    {
        $customClient = new \GuzzleHttp\Client();
        
        $this->app->bind('custom.http.client', function () use ($customClient) {
            return $customClient;
        });

        $this->app['config']->set('services.web2sms.http_client', 'custom.http.client');

        // Force rebind
        $this->app->forgetInstance(Web2sms::class);

        $client = $this->app->make(Web2sms::class);

        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_multiple_account_configurations()
    {
        // Test that different configurations can be used
        $this->app['config']->set('services.web2sms', [
            'key' => 'key1',
            'secret' => 'secret1',
            'sms_from' => 'SENDER1',
            'account_type' => 'prepaid',
        ]);

        $client1 = $this->app->make(Web2sms::class);
        $this->assertEquals('/prepaid/message', $client1->selectedEndpointURL);
    }
}