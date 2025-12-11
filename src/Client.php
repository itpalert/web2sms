<?php

namespace ITPalert\Web2sms;

use Psr\Http\Client\ClientInterface;
use RuntimeException;

use ITPalert\Web2sms\Credentials\CredentialsInterface;
use ITPalert\Web2sms\Credentials\Basic;

use Psr\Http\Message\ResponseInterface;

use ITPalert\Web2sms\Responses\SendResponse;
use ITPalert\Web2sms\Responses\StatusResponse;
use ITPalert\Web2sms\Responses\DeleteResponse;
use ITPalert\Web2sms\Responses\BalanceResponse;

class Client
{
    const SMS_PLATFORM_URL            = "https://www.web2sms.ro";        // Mandatory 

    const SMS_URL_PREPAIID            = "/prepaid/message";              // Mandatory
    const SMS_URL_POSTPAID            = "/send/message";                 // Mandatory

    /**
     * API Credentials
     *
     * @var CredentialsInterface
     */
    protected $credentials;

    /**
     * Http Client
     *
     * @var \Psr\Http\Client\ClientInterface
     */
    protected $client;


    /**
     * @var array
     */
    protected $options = [];

    /**
     * @string
     */
    public $apiUrl;

    /**
     * @string
     */
    public $selectedEndpointURL;

    /**
     * Error handler to use when reviewing API responses
     *
     * @var callable
     */
    protected $exceptionErrorHandler;

    /**
     * Create a new Web2sms instance.
     *
     * @param  \GuzzleHttp\Client  $http
     * @param  array  $config
     */
    public function __construct( 
        CredentialsInterface $credentials,
        array $options = [],
        ?ClientInterface $client = null
    ) {
        if (is_null($client)) {
            $client = new \GuzzleHttp\Client();
        }

        $this->setHttpClient($client);

        // Make sure we know how to use the credentials
        if (
            !($credentials instanceof Basic)
        ) {
            throw new RuntimeException('unknown credentials type: ' . $credentials::class);
        }
       
        $this->credentials = $credentials;

        $this->options = array_merge($this->options, $options);

        $this->setApiUrl();
    }

     /**
     * Set the Http Client to used to make API requests.
     *
     * This allows the default http client to be swapped out for a HTTPlug compatible
     * replacement.
     */
    public function setHttpClient(ClientInterface $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get the Http Client used to make API requests.
     */
    public function getHttpClient(): ClientInterface
    {
        return $this->client;
    }

    public function setApiUrl(): self
    {
        // Set the default URLs. Keep the constants for
        // backwards compatibility
        $this->apiUrl = static::SMS_PLATFORM_URL;
        
        switch($this->credentials->accountType) {
            case 'postpaid':
                $this->selectedEndpointURL = static::SMS_URL_POSTPAID;
                break;

            case 'prepaid' :
                $this->selectedEndpointURL = static::SMS_URL_PREPAIID;
                break;

            default:
                $this->selectedEndpointURL = static::SMS_URL_PREPAIID;
        }

        return $this;
    }

    public function send(SMS $message): SendResponse
    {
        $message->verifyMessage();

        $sender = $message->getFrom() ?: ($this->options['sms_from'] ?? '');

        // Build signature
        $signatureString = implode('', [
            $this->credentials->api_key,
            $message->getNonce(),
            'POST',
            $this->selectedEndpointURL,
            $sender,
            $message->getTo(),
            $message->getMessage(),
            $message->getDisplayedMessage(),
            $message->getSchedule(),
            $message->getDeliveryReceiptCallback(),
            $this->credentials->api_secret,
        ]);

        $signature = hash('sha512', $signatureString);

        $payload = json_encode(array_merge(
            $message->toArray(), 
            [
                'apiKey' => $this->credentials->api_key,
                'sender' => $sender,
            ]
        ));

        $response = $this->getHttpClient()->post($this->apiUrl . $this->selectedEndpointURL, [
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json', 
                'Content-length' => strlen($payload),
            ],
            'auth' => [
                $this->credentials->api_key,
                $signature
            ],
            'body' => $payload,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $response->getBody()->rewind();

        // Handle HTTP errors
        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $e = $this->getException($response);
            $e->setEntity($payload);
            throw $e;
        }

        return new SendResponse(
            data: $body,
            errorCode: $body['error']['code'] ?? -1,
            errorMessage: $body['error']['message'] ?? 'Unknown error'
        );
    }

    public function get(string $id): StatusResponse
    {
        $nonce = time();

        // Build signature
        $signatureString = implode('', [
            $this->credentials->api_key,
            $nonce,
            'GET',
            $this->selectedEndpointURL,
            $id,
            $this->credentials->api_secret,
        ]);

        $signature = hash('sha512', $signatureString);

        $payload = json_encode([
            'apiKey' => $this->credentials->api_key,
            'id' => $id,
            'nonce' => $nonce,
        ]); // json DATA

        $response = $this->getHttpClient()->get($this->apiUrl . $this->selectedEndpointURL, [
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json', 
                'Content-length' => strlen($payload),
            ],
            'auth' => [
                $this->credentials->api_key,
                $signature
            ],
            'body' => $payload,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $response->getBody()->rewind();

        // Handle HTTP errors
        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $e = $this->getException($response);
            $e->setEntity($payload);
            throw $e;
        }

        return new StatusResponse(
            data: $body,
            errorCode: $body['error']['code'] ?? -1,
            errorMessage: $body['error']['message'] ?? 'Unknown error'
        );
    }

    public function delete(string $id): DeleteResponse
    {
        $nonce = time();

        // Build signature
        $signatureString = implode('', [
            $this->credentials->api_key,
            $nonce,
            'DELETE',
            $this->selectedEndpointURL,
            $id,
            $this->credentials->api_secret,
        ]);

        $signature = hash('sha512', $signatureString);

        $payload = json_encode([
            'apiKey' => $this->credentials->api_key,
            'id' => $id,
            'nonce' => $nonce,
        ]); // json DATA

        $response = $this->getHttpClient()->request("DELETE", $this->apiUrl . $this->selectedEndpointURL, [
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json', 
                'Content-length' => strlen($payload),
            ],
            'auth' => [
                $this->credentials->api_key,
                $signature
            ],
            'body' => $payload,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $response->getBody()->rewind();

        // Handle HTTP errors
        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $e = $this->getException($response);
            $e->setEntity($payload);
            throw $e;
        }

        return new DeleteResponse(
            data: $body,
            errorCode: $body['error']['code'] ?? -1,
            errorMessage: $body['error']['message'] ?? 'Unknown error'
        );
    }

    public function balance(): BalanceResponse
    {
        $nonce = time();

        // Build signature
        $signatureString = implode('', [
            $this->credentials->api_key,
            $nonce,
            'BALANCE',
            $this->selectedEndpointURL,
            $this->credentials->api_secret,
        ]);

        $signature = hash('sha512', $signatureString);

        $payload = json_encode([
            'apiKey' => $this->credentials->api_key,
            'nonce' => $nonce,
        ]); // json DATA

        $response = $this->getHttpClient()->request("BALANCE", $this->apiUrl . $this->selectedEndpointURL, [
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json', 
                'Content-length' => strlen($payload),
            ],
            'auth' => [
                $this->credentials->api_key,
                $signature
            ],
            'body' => $payload,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $response->getBody()->rewind();

        // Handle HTTP errors
        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $e = $this->getException($response);
            $e->setEntity($payload);
            throw $e;
        }

        return new BalanceResponse(
            data: $body,
            errorCode: $body['error']['code'] ?? -1,
            errorMessage: $body['error']['message'] ?? 'Unknown error'
        );
    }

    public function getExceptionErrorHandler(): callable
    {
        if (is_null($this->exceptionErrorHandler)) {
            return new APIExceptionHandler();
        }

        return $this->exceptionErrorHandler;
    }

    protected function getException(ResponseInterface $response)
    {
        return $this->getExceptionErrorHandler()($response);
    }
}