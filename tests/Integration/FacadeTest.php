<?php

namespace ITPalert\Web2sms\Tests\Integration;

use ITPalert\Web2sms\Client;
use ITPalert\Web2sms\Contracts\Client as ClientContract;
use ITPalert\Web2sms\Facades\Web2sms;
use ITPalert\Web2sms\Tests\TestCase;

class FacadeTest extends TestCase
{
    public function test_facade_accessor_returns_correct_class()
    {
        $reflection = new \ReflectionClass(Web2sms::class);
        $method = $reflection->getMethod('getFacadeAccessor');
        $method->setAccessible(true);

        $accessor = $method->invoke(null);

        // The facade resolves the contract now, so a fake can stand behind it
        // without pretending to be the concrete HTTP client.
        $this->assertEquals(ClientContract::class, $accessor);
    }

    public function test_facade_is_properly_registered()
    {
        $this->assertTrue(class_exists(Web2sms::class));
    }

    public function test_facade_resolves_from_container()
    {
        $instance = Web2sms::getFacadeRoot();

        $this->assertInstanceOf(ClientContract::class, $instance);
        // The base test case selects the api driver, so this is the real one.
        $this->assertInstanceOf(Client::class, $instance);
    }
}
