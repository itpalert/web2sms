<?php

namespace ITPalert\Web2sms\Tests;

use PHPUnit\Framework\TestCase;
use ITPalert\Web2sms\Client;
use ITPalert\Web2sms\SMS;
use ITPalert\Web2sms\Credentials\Basic;
use ITPalert\Web2sms\Responses\SendResponse;
use ITPalert\Web2sms\Responses\StatusResponse;
use ITPalert\Web2sms\Responses\DeleteResponse;
use ITPalert\Web2sms\Responses\BalanceResponse;
use ITPalert\Web2sms\Exceptions\Exception;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Client as GuzzleClient;

class ClientTest extends TestCase
{
    private function createMockHttpClient(int $statusCode, array $body): ClientInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')
            ->willReturn(json_encode($body));
        $stream->method('rewind');
        $stream->method('__toString')
            ->willReturn(json_encode($body));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')
            ->willReturn($statusCode);
        $response->method('getBody')
            ->willReturn($stream);

        // Create a mock Guzzle client (which has the methods we need)
        $httpClient = $this->createMock(GuzzleClient::class);
        $httpClient->method('request')
            ->willReturn($response);
        $httpClient->method('post')
            ->willReturn($response);
        $httpClient->method('get')
            ->willReturn($response);

        return $httpClient;
    }

    public function test_client_can_be_instantiated_with_credentials()
    {
        $credentials = new Basic('test_key', 'test_secret', 'prepaid');
        $httpClient = $this->createMock(GuzzleClient::class);
        
        $client = new Client($credentials, [], $httpClient);
        
        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_send_returns_success_response()
    {
        $httpClient = $this->createMockHttpClient(201, [
            'id' => 'test_message_id_123',
            'error' => [
                'code' => 0,
                'message' => 'IDS_Prepaid_MessageController_E_OK'
            ]
        ]);

        $credentials = new Basic('test_key', 'test_secret', 'prepaid');
        $client = new Client($credentials, [], $httpClient);

        $sms = new SMS('0712345678', 'ALERT', 'Test message', 'text');
        $response = $client->send($sms);

        $this->assertInstanceOf(SendResponse::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertEquals('test_message_id_123', $response->getMessageId());
        $this->assertEquals(0, $response->getErrorCode());
    }

    public function test_send_returns_error_response()
    {
        $httpClient = $this->createMockHttpClient(201, [
            'error' => [
                'code' => 268435459,
                'message' => 'Parameter message is empty'
            ]
        ]);

        $credentials = new Basic('test_key', 'test_secret', 'prepaid');
        $client = new Client($credentials, [], $httpClient);

        $sms = new SMS('0712345678', 'ALERT', 'Test message', 'text');
        $response = $client->send($sms);

        $this->assertInstanceOf(SendResponse::class, $response);
        $this->assertFalse($response->isSuccess());
        $this->assertEquals(268435459, $response->getErrorCode());
        $this->assertEquals('Parameter message is empty', $response->getErrorMessage());
    }

    public function test_send_throws_exception_on_http_error()
    {
        $this->expectException(Exception::class);

        $httpClient = $this->createMockHttpClient(401, [
            'error' => [
                'code' => 401,
                'message' => 'Unauthorized'
            ]
        ]);

        $credentials = new Basic('test_key', 'test_secret', 'prepaid');
        $client = new Client($credentials, [], $httpClient);

        $sms = new SMS('0712345678', 'ALERT', 'Test message', 'text');
        $client->send($sms);
    }

    public function test_get_returns_status_response()
    {
        $httpClient = $this->createMockHttpClient(201, [
            'status' => '2',
            'error' => [
                'code' => 0,
                'message' => 'IDS_Prepaid_MessageController_E_OK'
            ]
        ]);

        $credentials = new Basic('test_key', 'test_secret', 'prepaid');
        $client = new Client($credentials, [], $httpClient);

        $response = $client->get('test_message_id');

        $this->assertInstanceOf(StatusResponse::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertEquals('2', $response->getStatus());
    }

    public function test_delete_returns_delete_response()
    {
        $httpClient = $this->createMockHttpClient(201, [
            'result' => 'DELETED',
            'error' => [
                'code' => 0,
                'message' => 'IDS_Prepaid_MessageController_E_OK'
            ]
        ]);

        $credentials = new Basic('test_key', 'test_secret', 'prepaid');
        $client = new Client($credentials, [], $httpClient);

        $response = $client->delete('test_message_id');

        $this->assertInstanceOf(DeleteResponse::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->isDeleted());
    }

    public function test_balance_returns_balance_response()
    {
        $httpClient = $this->createMockHttpClient(201, [
            'result' => 'BALANCE',
            'error' => [
                'code' => 0,
                'message' => '150.50'
            ]
        ]);

        $credentials = new Basic('test_key', 'test_secret', 'prepaid');
        $client = new Client($credentials, [], $httpClient);

        $response = $client->balance();

        $this->assertInstanceOf(BalanceResponse::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertEquals('150.50', $response->getBalance());
    }

    public function test_client_uses_prepaid_endpoint_by_default()
    {
        $credentials = new Basic('test_key', 'test_secret');
        $httpClient = $this->createMock(GuzzleClient::class);
        
        $client = new Client($credentials, [], $httpClient);
        
        $this->assertEquals('/prepaid/message', $client->selectedEndpointURL);
    }

    public function test_client_uses_postpaid_endpoint_when_specified()
    {
        $credentials = new Basic('test_key', 'test_secret', 'postpaid');
        $httpClient = $this->createMock(GuzzleClient::class);
        
        $client = new Client($credentials, [], $httpClient);
        
        $this->assertEquals('/send/message', $client->selectedEndpointURL);
    }

    public function test_client_uses_default_sender_from_options()
    {
        $httpClient = $this->createMockHttpClient(201, [
            'id' => 'test_id',
            'error' => ['code' => 0, 'message' => 'OK']
        ]);

        $credentials = new Basic('test_key', 'test_secret', 'prepaid');
        $client = new Client($credentials, ['sms_from' => 'DEFAULT_SENDER'], $httpClient);

        $sms = new SMS('0712345678', '', 'Test message', 'text');
        $response = $client->send($sms);

        $this->assertTrue($response->isSuccess());
    }
}