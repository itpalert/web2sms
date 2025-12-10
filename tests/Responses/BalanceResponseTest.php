<?php

namespace ITPalert\Web2sms\Tests\Responses;

use PHPUnit\Framework\TestCase;
use ITPalert\Web2sms\Responses\BalanceResponse;

class BalanceResponseTest extends TestCase
{
    public function test_get_balance_returns_balance_from_error_message()
    {
        $response = new BalanceResponse(
            data: ['result' => 'BALANCE'],
            errorCode: 0,
            errorMessage: '150.50'
        );

        $this->assertEquals('150.50', $response->getBalance());
    }

    public function test_get_balance_returns_null_when_not_balance_result()
    {
        $response = new BalanceResponse(
            data: ['result' => 'OTHER'],
            errorCode: 0,
            errorMessage: '150.50'
        );

        $this->assertNull($response->getBalance());
    }

    public function test_get_balance_returns_null_when_error()
    {
        $response = new BalanceResponse(
            data: ['result' => 'BALANCE'],
            errorCode: 1,
            errorMessage: 'Error'
        );

        $this->assertNull($response->getBalance());
    }
}