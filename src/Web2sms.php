<?php

namespace ITPalert\Web2sms;

use GuzzleHttp\ClientInterface;
use RuntimeException;

use ITPalert\Web2sms\Credentials\Basic;

class Web2sms
{
    /**
     * The web2sms configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * The HttpClient instance, if provided.
     *
     * @var \Psr\Http\Client\ClientInterface
     */
    protected $client;

    /**
     * Create a new Web2sms instance.
     *
     * @param  array  $config
     * @param  \Psr\Http\Client\ClientInterface|null  $client
     * @return void
     */
    public function __construct(array $config = [], ?ClientInterface $client = null)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * Create a new Web2sms instance.
     *
     * @param  array  $config
     * @param  \Psr\Http\Client\ClientInterface|null  $client
     * @return static
     */
    public static function make(array $config, ?ClientInterface $client = null)
    {
        return new static($config, $client);
    }

    /**
     * Create a new Web2sms Client.
     *
     * @return ITPalert\Web2sms\Client
     *
     * @throws \RuntimeException
     */
    public function client()
    {
        $credentials = null;

        if ($apiSecret = $this->config['secret'] ?? null) {
            $credentials = new Basic($this->config['key'], $apiSecret, $this->config['account_type']);
        }

        if (!$credentials) {
            throw new RuntimeException(
                'Please provide your Web2sms API credentials.'
            );
        } 

        return new Client($credentials, $this->config, $this->client);
    }
}