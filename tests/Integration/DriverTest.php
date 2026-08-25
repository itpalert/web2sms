<?php

namespace ITPalert\Web2sms\Tests\Integration;

use ITPalert\Web2sms\Client;
use ITPalert\Web2sms\Contracts\Client as ClientContract;
use ITPalert\Web2sms\Facades\Web2sms as Web2smsFacade;
use ITPalert\Web2sms\SMS;
use ITPalert\Web2sms\Testing\Web2smsFake;
use ITPalert\Web2sms\Tests\TestCase;

/**
 * Which client the container hands out, and what the fake guarantees.
 *
 * The behaviour worth protecting here is the default: an application whose
 * test suite has never thought about SMS must not be able to send one. Every
 * other outbound integration is switched off in tests by a setting somebody
 * has to remember to add, and a text message is the one kind of outbound that
 * costs money per attempt and lands on a stranger's handset.
 */
class DriverTest extends TestCase
{
    /**
     * Drop the base class's explicit api driver, so these tests see what an
     * application with no driver configured would get.
     */
    protected function web2smsConfig(): array
    {
        return ['driver' => null];
    }

    public function test_it_defaults_to_the_fake_while_testing()
    {
        $this->assertInstanceOf(Web2smsFake::class, $this->app->make(ClientContract::class));
    }

    public function test_naming_the_api_driver_opts_back_in_to_real_sending()
    {
        $this->app['config']->set('services.web2sms.driver', 'api');
        $this->app->forgetInstance(ClientContract::class);

        $this->assertInstanceOf(Client::class, $this->app->make(ClientContract::class));
    }

    public function test_the_null_and_array_aliases_also_reach_the_fake()
    {
        foreach (['fake', 'array', 'null'] as $driver) {
            $this->app['config']->set('services.web2sms.driver', $driver);
            $this->app->forgetInstance(ClientContract::class);

            $this->assertInstanceOf(
                Web2smsFake::class,
                $this->app->make(ClientContract::class),
                "Driver [{$driver}] should not be able to send."
            );
        }
    }

    public function test_the_log_driver_writes_what_it_swallowed()
    {
        $this->app['config']->set('services.web2sms.driver', 'log');
        $this->app->forgetInstance(ClientContract::class);

        $client = $this->app->make(ClientContract::class);

        $this->assertInstanceOf(Web2smsFake::class, $client);

        $client->send(new SMS('0712345678', 'TEST', 'Hello there', 'text'));

        // Still recorded, so log mode is assertable too.
        $this->assertCount(1, $client->sent());
    }

    public function test_the_fake_records_messages_rather_than_sending_them()
    {
        $fake = Web2smsFacade::fake();

        Web2smsFacade::send(new SMS('0712345678', 'TEST', 'First', 'text'));
        Web2smsFacade::send(new SMS('0755000111', 'TEST', 'Second', 'text'));

        $fake->assertSentCount(2)
            ->assertSentTo('0712345678')
            ->assertSentTo('0755000111', fn (SMS $sms) => $sms->getMessage() === 'Second')
            ->assertSent(fn (SMS $sms) => str_contains($sms->getMessage(), 'First'))
            ->assertNotSent(fn (SMS $sms) => $sms->getTo() === '0700000000');

        // And nothing reached the wire.
        $this->assertSame(0, $this->requestCount());
    }

    public function test_the_fake_still_rejects_a_message_the_api_would_reject()
    {
        $fake = Web2smsFacade::fake();

        // A fake that accepts anything hides bugs until production, so the
        // same validation the real client runs before dialling out runs here.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('INVALID_RECIVER');

        $fake->send(new SMS('', 'TEST', 'Nobody to send this to', 'text'));
    }

    public function test_the_fake_records_nothing_when_validation_fails()
    {
        $fake = Web2smsFacade::fake();

        try {
            $fake->send(new SMS('', 'TEST', 'Nobody to send this to', 'text'));
        } catch (\RuntimeException) {
            // Expected.
        }

        $fake->assertNothingSent();
    }

    public function test_assert_nothing_sent_passes_on_a_quiet_suite()
    {
        Web2smsFacade::fake()->assertNothingSent();
    }

    public function test_the_fake_reports_a_balance_a_test_chooses()
    {
        $fake = Web2smsFacade::fake()->shouldReportBalance(42.5);

        $this->assertSame(42.5, Web2smsFacade::balance()->toArray()['balance']);
        $this->assertSame(1, $fake->balanceCallCount());
    }

    public function test_calling_fake_twice_keeps_the_same_recorder()
    {
        $first = Web2smsFacade::fake();
        Web2smsFacade::send(new SMS('0712345678', 'TEST', 'Only once', 'text'));

        $second = Web2smsFacade::fake();

        $this->assertSame($first, $second);
        $second->assertSentCount(1);
    }
}
