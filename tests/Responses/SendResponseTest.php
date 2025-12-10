<?php

namespace ITPalert\Web2sms\Tests\Responses;

use PHPUnit\Framework\TestCase;
use ITPalert\Web2sms\Responses\SendResponse;

class SendResponseTest extends TestCase
{
    public function test_is_success_returns_true_for_zero_error_code()
    {
        $response = new SendResponse(
            data: ['id' => 'test_id'],
            errorCode: 0,
            errorMessage: 'OK'
        );

        $this->assertTrue($response->isSuccess());
    }

    public function test_is_success_returns_false_for_non_zero_error_code()
    {
        $response = new SendResponse(
            data: [],
            errorCode: 268435459,
            errorMessage: 'Error'
        );

        $this->assertFalse($response->isSuccess());
    }

    public function test_get_message_id_returns_id()
    {
        $response = new SendResponse(
            data: ['id' => 'message_id_123'],
            errorCode: 0,
            errorMessage: 'OK'
        );

        $this->assertEquals('message_id_123', $response->getMessageId());
    }

    public function test_get_message_id_returns_null_when_missing()
    {
        $response = new SendResponse(
            data: [],
            errorCode: 268435459,
            errorMessage: 'Error'
        );

        $this->assertNull($response->getMessageId());
    }

    public function test_to_array_returns_raw_data()
    {
        $data = ['id' => 'test', 'extra' => 'value'];
        $response = new SendResponse(
            data: $data,
            errorCode: 0,
            errorMessage: 'OK'
        );

        $this->assertEquals($data, $response->toArray());
    }
}