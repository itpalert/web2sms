<?php

namespace ITPalert\Web2sms\Tests\Responses;

use PHPUnit\Framework\TestCase;
use ITPalert\Web2sms\Responses\StatusResponse;

class StatusResponseTest extends TestCase
{
    public function test_get_status_returns_status()
    {
        $response = new StatusResponse(
            data: ['status' => '2'],
            errorCode: 0,
            errorMessage: 'OK'
        );

        $this->assertEquals('2', $response->getStatus());
    }

    public function test_get_status_returns_null_when_missing()
    {
        $response = new StatusResponse(
            data: [],
            errorCode: 0,
            errorMessage: 'OK'
        );

        $this->assertNull($response->getStatus());
    }
}