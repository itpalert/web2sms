<?php

namespace ITPalert\Web2sms\Tests\Responses;

use PHPUnit\Framework\TestCase;
use ITPalert\Web2sms\Responses\DeleteResponse;

class DeleteResponseTest extends TestCase
{
    public function test_is_deleted_returns_true_when_deleted()
    {
        $response = new DeleteResponse(
            data: ['result' => 'DELETED'],
            errorCode: 0,
            errorMessage: 'OK'
        );

        $this->assertTrue($response->isDeleted());
    }

    public function test_is_deleted_returns_false_when_not_deleted()
    {
        $response = new DeleteResponse(
            data: ['result' => 'FAILED'],
            errorCode: 1,
            errorMessage: 'Error'
        );

        $this->assertFalse($response->isDeleted());
    }

    public function test_is_deleted_returns_false_when_result_missing()
    {
        $response = new DeleteResponse(
            data: [],
            errorCode: 0,
            errorMessage: 'OK'
        );

        $this->assertFalse($response->isDeleted());
    }
}