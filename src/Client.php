<?php

namespace ITPalert\Web2sms;

use Psr\Http\Client\ClientInterface;
use Composer\InstalledVersions;
use RuntimeException;

use ITPalert\Web2sms\Credentials\CredentialsInterface;
use ITPalert\Web2sms\Credentials\Basic;

use Psr\Http\Message\ResponseInterface;

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
            // Since the user did not pass a client, try and make a client
            // using the Guzzle 6 adapter or Guzzle 7 (depending on availability)
            list($guzzleVersion) = explode('@', InstalledVersions::getVersion('guzzlehttp/guzzle'), 1);
            $guzzleVersion = (float) $guzzleVersion;

            if ($guzzleVersion >= 6.0 && $guzzleVersion < 7) {
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                /** @noinspection PhpUndefinedNamespaceInspection */
                /** @noinspection PhpUndefinedClassInspection */
                $client = new \Http\Adapter\Guzzle6\Client();
            }

            if ($guzzleVersion >= 7.0 && $guzzleVersion < 8.0) {
                $client = new \GuzzleHttp\Client();
            }
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

    public function send(SMS $message): ?array
    {
        $message->verifyMessage();

        $sender = $message->getFrom() ?? $this->options['sms_from'] ?? '';

        /*signature*/
        $string = $this->credentials->api_key;
        $string .= $message->getNonce();
        $string .= "POST";
        $string .= $this->selectedEndpointURL;
        $string .= $sender;
        $string .= $message->getTo();
        $string .= $message->getMessage();
        $string .= $message->getDisplayedMessage();
        $string .= $message->getSchedule();
        $string .= $message->getDeliveryReceiptCallback();
        $string .= $this->credentials->api_secret;

        $signature = hash('sha512', $string);
        /*signature*/

        $payload = json_encode(array_merge(
            $message->toArray(), 
            [
                'apiKey' => $this->credentials->api_key,
                'sender' => $sender,
            ]
        )); // json DATA

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

        $status = $response->getStatusCode();

        if (($status < 200 || $status > 299)) {
            $e = $this->getException($response);

            if ($e) {
                $e->setEntity($payload);

                throw $e;
            }
        }

        $response->getBody()->rewind();

        return json_decode($response->getBody()->getContents(), true);
    }

    public function get(string $id): ?array
    {
        $nonce = time();

        /*signature*/
        $string = $this->credentials->api_key;
        $string .= $nonce;
        $string .= "GET";
        $string .= $this->selectedEndpointURL;
        $string .= $id;
        $string .= $this->credentials->api_secret;

        $signature = hash('sha512', $string);
        /*signature*/

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

        $status = $response->getStatusCode();

        if (($status < 200 || $status > 299)) {
            $e = $this->getException($response);

            if ($e) {
                $e->setEntity($payload);

                throw $e;
            }
        }

        $response->getBody()->rewind();

        return json_decode($response->getBody()->getContents(), true);
    }

    public function delete(string $id): ?array
    {
        $nonce = time();

        /*signature*/
        $string = $this->credentials->api_key;
        $string .= $nonce;
        $string .= "DELETE";
        $string .= $this->selectedEndpointURL;
        $string .= $id;
        $string .= $this->credentials->api_secret;

        $signature = hash('sha512', $string);
        /*signature*/

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

        $status = $response->getStatusCode();

        if (($status < 200 || $status > 299)) {
            $e = $this->getException($response);

            if ($e) {
                $e->setEntity($payload);

                throw $e;
            }
        }

        $response->getBody()->rewind();

        return json_decode($response->getBody()->getContents(), true);
    }

    public function balance(): ?array
    {
        $nonce = time();

        /*signature*/
        $string = $this->credentials->api_key;
        $string .= $nonce;
        $string .= "BALANCE";
        $string .= $this->selectedEndpointURL;
        $string .= $this->credentials->api_secret;

        $signature = hash('sha512', $string);
        /*signature*/

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

        $status = $response->getStatusCode();

        if (($status < 200 || $status > 299)) {
            $e = $this->getException($response);

            if ($e) {
                $e->setEntity($payload);

                throw $e;
            }
        }

        $response->getBody()->rewind();

        return json_decode($response->getBody()->getContents(), true);
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