<?php

namespace ITPalert\Web2sms\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use ITPalert\Web2sms\Facades\Web2sms as Web2smsFacade;
use ITPalert\Web2sms\Web2smsServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Psr\Http\Message\RequestInterface;

/**
 * Base for the tests that boot Laravel.
 *
 * These exercise the real Client on purpose: its request signing, endpoint
 * selection and response parsing are the parts most worth covering, and the
 * fake deliberately implements none of them. So the driver here is `api`, not
 * the fake the testing environment would otherwise default to.
 *
 * What makes that safe is the handler underneath. Every request is served by a
 * Guzzle MockHandler, so the real client runs end to end and nothing reaches
 * the network. Exercising the client and texting a stranger are different
 * things, and only the second one is a problem.
 */
abstract class TestCase extends BaseTestCase
{
    protected ?MockHandler $httpMock = null;

    /** @var array<int, array<string, mixed>> */
    protected array $httpHistory = [];

    protected function getPackageProviders($app)
    {
        return [Web2smsServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return ['Web2sms' => Web2smsFacade::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app->bind('tests.http-client', fn () => $this->httpClient());

        $app['config']->set('services.web2sms', array_merge([
            // The real client, against a handler that cannot leave the machine.
            'driver' => 'api',
            'key' => 'test_api_key',
            'secret' => 'test_api_secret',
            'sms_from' => 'TEST',
            'account_type' => 'prepaid',
            'http_client' => 'tests.http-client',
        ], $this->web2smsConfig()));
    }

    /**
     * Per-test config overrides.
     *
     * @return array<string, mixed>
     */
    protected function web2smsConfig(): array
    {
        return [];
    }

    /** Queue the next canned API response. */
    protected function willRespondWith(int $status, array $body): self
    {
        $this->httpClient();

        $this->httpMock->append(new Response($status, [], json_encode($body)));

        return $this;
    }

    protected function lastRequest(): ?RequestInterface
    {
        $last = end($this->httpHistory);

        return $last === false ? null : $last['request'];
    }

    protected function requestCount(): int
    {
        return count($this->httpHistory);
    }

    /**
     * A Guzzle client wired to a mock handler, built once per test.
     *
     * Seeded with a success response so a test that only cares about the
     * request it produced does not have to queue one first.
     */
    protected function httpClient(): GuzzleClient
    {
        if ($this->httpMock === null) {
            $this->httpMock = new MockHandler([
                new Response(201, [], json_encode([
                    'id' => 'test_id',
                    'error' => ['code' => 0, 'message' => 'OK'],
                ])),
            ]);

            $stack = HandlerStack::create($this->httpMock);
            $stack->push(Middleware::history($this->httpHistory));

            $this->guzzle = new GuzzleClient(['handler' => $stack]);
        }

        return $this->guzzle;
    }

    protected GuzzleClient $guzzle;
}
