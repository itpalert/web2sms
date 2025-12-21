<?php

namespace ITPalert\Web2sms;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use ITPalert\Web2sms\Credentials\Basic;
use ITPalert\Web2sms\Credentials\CredentialsInterface;
use ITPalert\Web2sms\Responses\BalanceResponse;
use ITPalert\Web2sms\Responses\DeleteResponse;
use ITPalert\Web2sms\Responses\SendResponse;
use ITPalert\Web2sms\Responses\StatusResponse;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class Client
{
    public const SMS_PLATFORM_URL = 'https://www.web2sms.ro'; // Mandatory

    public const SMS_URL_PREPAIID = '/prepaid/message'; // Mandatory
    public const SMS_URL_POSTPAID = '/send/message'; // Mandatory

    /**
     * API Credentials
     *
     * @var CredentialsInterface
     */
    protected CredentialsInterface $credentials;

    /**
     * HTTP client used to make API requests (Guzzle).
     *
     * @var ClientInterface
     */
    protected ClientInterface $client;

    /**
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * @var string
     */
    public string $apiUrl;

    /**
     * @var string
     */
    public string $selectedEndpointURL;

    /**
     * Error handler to use when reviewing API responses.
     *
     * @var callable|null
     */
    protected $exceptionErrorHandler;

    /**
     * Create a new Web2sms Client instance.
     *
     * @param CredentialsInterface     $credentials
     * @param array<string, mixed>     $options
     * @param ClientInterface|null     $client
     */
    public function __construct(
        CredentialsInterface $credentials,
        array $options = [],
        ?ClientInterface $client = null
    ) {
        $client ??= new GuzzleClient();

        $this->setHttpClient($client);

        // Make sure we know how to use the credentials
        if (!($credentials instanceof Basic)) {
            throw new RuntimeException('unknown credentials type: ' . $credentials::class);
        }

        $this->credentials = $credentials;
        $this->options = array_merge($this->options, $options);

        $this->setApiUrl();
    }

    /**
     * Set the HTTP client used to make API requests (Guzzle).
     */
    public function setHttpClient(ClientInterface $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get the HTTP client used to make API requests (Guzzle).
     */
    public function getHttpClient(): ClientInterface
    {
        return $this->client;
    }

    public function setApiUrl(): self
    {
        $this->apiUrl = static::SMS_PLATFORM_URL;

        switch ($this->credentials->accountType) {
            case 'postpaid':
                $this->selectedEndpointURL = static::SMS_URL_POSTPAID;
                break;

            case 'prepaid':
            default:
                $this->selectedEndpointURL = static::SMS_URL_PREPAIID;
        }

        return $this;
    }

    public function send(SMS $message): SendResponse
    {
        $message->verifyMessage();

        $sender = $message->getFrom() ?: ($this->options['sms_from'] ?? '');
        $nonce = time();

        $signatureString = implode('', [
            $this->credentials->api_key,
            $nonce,
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
                'nonce' => $nonce,
            ]
        ));

        $response = $this->getHttpClient()->request('POST', $this->apiUrl . $this->selectedEndpointURL, [
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Content-length' => strlen($payload),
            ],
            'auth' => [
                $this->credentials->api_key,
                $signature,
            ],
            'body' => $payload,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $response->getBody()->rewind();

        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $e = $this->getException($response);
            $e->setEntity($payload);
            throw $e;
        }

        return new SendResponse(
            data: $body,
            errorCode: $body['error']['code'] ?? -1,
            errorMessage: $body['error']['message'] ?? 'Unknown error',
        );
    }

    public function get(string $id): StatusResponse
    {
        $nonce = time();

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
        ]);

        $response = $this->getHttpClient()->request('GET', $this->apiUrl . $this->selectedEndpointURL, [
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Content-length' => strlen($payload),
            ],
            'auth' => [
                $this->credentials->api_key,
                $signature,
            ],
            'body' => $payload,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $response->getBody()->rewind();

        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $e = $this->getException($response);
            $e->setEntity($payload);
            throw $e;
        }

        return new StatusResponse(
            data: $body,
            errorCode: $body['error']['code'] ?? -1,
            errorMessage: $body['error']['message'] ?? 'Unknown error',
        );
    }

    public function delete(string $id): DeleteResponse
    {
        $nonce = time();

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
        ]);

        $response = $this->getHttpClient()->request("DELETE", $this->apiUrl . $this->selectedEndpointURL, [
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Content-length' => strlen($payload),
            ],
            'auth' => [
                $this->credentials->api_key,
                $signature,
            ],
            'body' => $payload,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $response->getBody()->rewind();

        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $e = $this->getException($response);
            $e->setEntity($payload);
            throw $e;
        }

        return new DeleteResponse(
            data: $body,
            errorCode: $body['error']['code'] ?? -1,
            errorMessage: $body['error']['message'] ?? 'Unknown error',
        );
    }

    public function balance(): BalanceResponse
    {
        $nonce = time();

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
        ]);

        $response = $this->getHttpClient()->request('BALANCE', $this->apiUrl . $this->selectedEndpointURL, [
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Content-length' => strlen($payload),
            ],
            'auth' => [
                $this->credentials->api_key,
                $signature,
            ],
            'body' => $payload,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $response->getBody()->rewind();

        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $e = $this->getException($response);
            $e->setEntity($payload);
            throw $e;
        }

        return new BalanceResponse(
            data: $body,
            errorCode: $body['error']['code'] ?? -1,
            errorMessage: $body['error']['message'] ?? 'Unknown error',
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
        return ($this->getExceptionErrorHandler())($response);
    }
}
