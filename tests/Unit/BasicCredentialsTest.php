<?php

namespace ITPalert\Web2sms\Tests\Unit\Credentials;

use ITPalert\Web2sms\Credentials\Basic;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class BasicCredentialsTest extends TestCase
{
    public function test_credentials_default_account_type_is_prepaid(): void
    {
        $credentials = new Basic('api_key', 'api_secret');

        $this->assertSame('prepaid', $credentials->accountType);
    }

    public function test_credentials_accept_postpaid_account_type(): void
    {
        $credentials = new Basic('api_key', 'api_secret', 'postpaid');

        $this->assertSame('postpaid', $credentials->accountType);
    }

    public function test_credentials_accept_prepaid_account_type(): void
    {
        $credentials = new Basic('api_key', 'api_secret', 'prepaid');

        $this->assertSame('prepaid', $credentials->accountType);
    }

    public function test_credentials_cast_values_to_strings(): void
    {
        $credentials = new Basic(123, 456, 'prepaid');

        $this->assertSame('123', $credentials->api_key);
        $this->assertSame('456', $credentials->api_secret);
        $this->assertSame('prepaid', $credentials->accountType);
    }

    public function test_as_array_returns_all_credentials(): void
    {
        $credentials = new Basic('key', 'secret', 'postpaid');

        $this->assertSame([
            'api_key' => 'key',
            'api_secret' => 'secret',
            'accountType' => 'postpaid',
        ], $credentials->asArray());
    }

    public function test_unknown_property_returns_null(): void
    {
        $credentials = new Basic('key', 'secret');

        $this->assertNull($credentials->non_existing_property ?? null);
    }

    public function test_invalid_account_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid account type');

        new Basic('api_key', 'api_secret', 'enterprise');
    }
}
