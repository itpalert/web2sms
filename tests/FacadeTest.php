<?php

namespace ITPalert\Web2sms\Tests;

use Orchestra\Testbench\TestCase;
use ITPalert\Web2sms\Facades\Web2sms;
use ITPalert\Web2sms\Web2smsServiceProvider;
use Illuminate\Support\Facades\Facade;

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
        // Use reflection to access protected method
        $reflection = new \ReflectionClass(Web2sms::class);
        $method = $reflection->getMethod('getFacadeAccessor');
        $method->setAccessible(true);
        
        $accessor = $method->invoke(null);
        
        $this->assertEquals(\ITPalert\Web2sms\Web2sms::class, $accessor);
    }

    public function test_facade_is_properly_registered()
    {
        $this->assertTrue(class_exists(\ITPalert\Web2sms\Facades\Web2sms::class));
    }

    public function test_facade_resolves_from_container()
    {
        $instance = Web2sms::getFacadeRoot();
        
        $this->assertInstanceOf(\ITPalert\Web2sms\Client::class, $instance);
    }
}