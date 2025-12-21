<?php

namespace ITPalert\Web2sms\Tests\Integration;

use Orchestra\Testbench\TestCase;
use ITPalert\Web2sms\Client;
use ITPalert\Web2sms\Web2smsServiceProvider;
use ITPalert\Web2sms\Facades\Web2sms;

class FacadeTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [Web2smsServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Web2sms' => Web2sms::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('services.web2sms', [
            'key' => 'test_key',
            'secret' => 'test_secret',
            'sms_from' => 'TEST',
            'account_type' => 'prepaid',
        ]);
    }

    public function test_facade_accessor_returns_correct_class()
    {
        $reflection = new \ReflectionClass(Web2sms::class);
        $method = $reflection->getMethod('getFacadeAccessor');
        $method->setAccessible(true);

        $accessor = $method->invoke(null);

        $this->assertEquals(Client::class, $accessor);
    }

    public function test_facade_is_properly_registered()
    {
        $this->assertTrue(class_exists(\ITPalert\Web2sms\Facades\Web2sms::class));
    }

    public function test_facade_resolves_from_container()
    {
        $instance = Web2sms::getFacadeRoot();

        $this->assertInstanceOf(Client::class, $instance);
    }
}
