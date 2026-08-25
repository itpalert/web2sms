<?php

namespace ITPalert\Web2sms\Tests\Integration;

use ITPalert\Web2sms\Client;
use ITPalert\Web2sms\Contracts\Client as ClientContract;
use ITPalert\Web2sms\Facades\Web2sms as Web2smsFacade;
use ITPalert\Web2sms\SMS;
use ITPalert\Web2sms\Testing\Web2smsFake;
use ITPalert\Web2sms\Tests\Fixtures\ReminderService;
use ITPalert\Web2sms\Tests\TestCase;

/**
 * Whether a package that depends on this one can actually fake it.
 *
 * The tests above prove this package's own client behaves. These prove the
 * thing consumers need: that constructor injection hands a downstream service
 * something safe by default, and that its assertions work without the consumer
 * writing any scaffolding of its own.
 *
 * This is the scenario that failed in production before the contract existed.
 * A consumer type-hinting the concrete client could only ever be given the real
 * one, so an application whose suite had never considered SMS sent real, billed
 * messages to the numbers hardcoded in its tests.
 */
class ConsumerFakingTest extends TestCase
{
    /** No driver set, so consumers see what an unconfigured application sees. */
    protected function web2smsConfig(): array
    {
        return ['driver' => null];
    }

    public function test_constructor_injection_hands_a_consumer_the_fake()
    {
        $service = $this->app->make(ReminderService::class);

        // The consumer wrote no test scaffolding at all. It simply asked for
        // the contract and was given something that cannot send.
        $reflection = new \ReflectionProperty($service, 'client');

        $this->assertInstanceOf(Web2smsFake::class, $reflection->getValue($service));
    }

    public function test_a_consumer_can_assert_on_what_its_code_sent()
    {
        $fake = $this->app->make(ClientContract::class);
        $service = $this->app->make(ReminderService::class);

        $service->remind('0712345678', 'Your ITP expires on 26-08-2026');

        $fake->assertSentCount(1)
            ->assertSentTo('0712345678', fn (SMS $sms) => str_contains($sms->getMessage(), 'ITP'));

        // The assertion passed and nothing reached the wire.
        $this->assertSame(0, $this->requestCount());
    }

    public function test_the_consumer_and_the_facade_share_one_recorder()
    {
        $service = $this->app->make(ReminderService::class);

        $service->remind('0755000111', 'From the service');

        // A consumer's test may reach for the facade rather than the container.
        // Both have to see the same messages, or assertions silently pass on an
        // empty recorder.
        Web2smsFacade::fake()->assertSentTo('0755000111');
    }

    public function test_a_consumer_still_gets_a_usable_response()
    {
        $service = $this->app->make(ReminderService::class);

        // Code under test often branches on the message id, so the fake has to
        // return a real response rather than null.
        $this->assertNotNull($service->remind('0712345678', 'Anything'));
    }

    public function test_a_consumer_that_wants_the_real_client_still_gets_it()
    {
        $this->app['config']->set('services.web2sms.driver', 'api');
        $this->app->forgetInstance(ClientContract::class);

        $service = $this->app->make(ReminderService::class);
        $reflection = new \ReflectionProperty($service, 'client');

        $this->assertInstanceOf(Client::class, $reflection->getValue($service));

        // And it really does send, through the mock handler rather than the
        // network, which is what proves the safe default is doing the work
        // rather than something else quietly swallowing everything.
        $service->remind('0712345678', 'Real path');

        $this->assertSame(1, $this->requestCount());
    }
}
