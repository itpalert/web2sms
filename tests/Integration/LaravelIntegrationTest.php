<?php

namespace ITPalert\Web2sms\Tests\Integration;

use ITPalert\Web2sms\Client;
use ITPalert\Web2sms\Contracts\Client as ClientContract;
use ITPalert\Web2sms\Facades\Web2sms as Web2smsFacade;
use ITPalert\Web2sms\SMS;
use ITPalert\Web2sms\Tests\TestCase;
use ITPalert\Web2sms\Web2smsServiceProvider;

/**
 * The container wiring, exercised against the real client.
 *
 * The base test case selects the api driver and puts a Guzzle MockHandler
 * underneath it, so everything here runs the genuine signing and parsing code
 * without a single packet leaving the machine.
 *
 * Note the container key throughout is the contract, not the concrete class.
 * The concrete one is deliberately not aliased: an alias resolves ahead of
 * bindings, so it would silently swallow a test's attempt to bind a stub and
 * send it to the live API instead.
 */
class LaravelIntegrationTest extends TestCase
{
    public function test_service_provider_registers_singleton_client()
    {
        $instance1 = $this->app->make(ClientContract::class);
        $instance2 = $this->app->make(ClientContract::class);

        $this->assertInstanceOf(Client::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    public function test_service_provider_loads_config_from_services()
    {
        $client = $this->app->make(ClientContract::class);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('https://www.web2sms.ro', $client->apiUrl);
        $this->assertEquals('/prepaid/message', $client->selectedEndpointURL);
    }

    public function test_service_provider_respects_account_type_postpaid()
    {
        $this->app['config']->set('services.web2sms.account_type', 'postpaid');

        $this->app->forgetInstance(ClientContract::class);

        $client = $this->app->make(ClientContract::class);

        $this->assertEquals('/send/message', $client->selectedEndpointURL);
    }

    public function test_service_provider_throws_exception_when_config_missing()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Web2sms configuration not found');

        $app = $this->createApplication();

        // Outside the testing environment, because in it a missing config is
        // no longer an error: the provider hands back the fake instead of
        // refusing to boot.
        $app['env'] = 'production';
        $app['config']->set('services.web2sms', []);

        $provider = new Web2smsServiceProvider($app);
        $provider->register();

        $app->make(ClientContract::class);
    }

    public function test_missing_config_is_not_fatal_while_testing()
    {
        $app = $this->createApplication();
        $app['config']->set('services.web2sms', []);

        $provider = new Web2smsServiceProvider($app);
        $provider->register();

        // A package with no credentials configured should not stop a test
        // suite from booting; it should simply not be able to send.
        $this->assertInstanceOf(ClientContract::class, $app->make(ClientContract::class));
    }

    public function test_facade_resolves_to_client()
    {
        $client = Web2smsFacade::getFacadeRoot();

        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_facade_can_call_client_methods()
    {
        $this->willRespondWith(201, [
            'id' => 'test_id',
            'error' => ['code' => 0, 'message' => 'OK'],
        ]);

        $sendResponse = Web2smsFacade::send(
            new SMS('0712345678', 'TEST', 'Test message', 'text')
        );

        $this->assertTrue($sendResponse->isSuccess());
        $this->assertEquals('test_id', $sendResponse->getMessageId());
    }

    public function test_it_posts_to_the_prepaid_endpoint_without_touching_the_network()
    {
        Web2smsFacade::send(new SMS('0712345678', 'TEST', 'Test message', 'text'));

        $request = $this->lastRequest();

        $this->assertNotNull($request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            'https://www.web2sms.ro/prepaid/message',
            (string) $request->getUri()
        );

        $payload = json_decode((string) $request->getBody(), true);

        $this->assertSame('0712345678', $payload['recipient'] ?? $payload['to'] ?? null);
        $this->assertSame('test_api_key', $payload['apiKey']);
    }

    public function test_service_provider_uses_custom_http_client_from_config()
    {
        $customClient = new \GuzzleHttp\Client();

        $this->app->bind('custom.http.client', fn () => $customClient);

        $this->app['config']->set('services.web2sms.http_client', 'custom.http.client');

        $this->app->forgetInstance(ClientContract::class);

        $client = $this->app->make(ClientContract::class);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame($customClient, $client->getHttpClient());
    }

    public function test_multiple_account_configurations()
    {
        $this->app['config']->set('services.web2sms', [
            'driver' => 'api',
            'key' => 'key1',
            'secret' => 'secret1',
            'sms_from' => 'SENDER1',
            'account_type' => 'prepaid',
            'http_client' => 'tests.http-client',
        ]);

        $this->app->forgetInstance(ClientContract::class);

        $client1 = $this->app->make(ClientContract::class);

        $this->assertEquals('/prepaid/message', $client1->selectedEndpointURL);
    }
}
