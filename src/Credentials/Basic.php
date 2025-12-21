<?php

namespace ITPalert\Web2sms\Credentials;

use InvalidArgumentException;

/**
 * Class Basic
 * Read-only container for api key and secret.
 *
 * @property string api_key
 * @property string api_secret
 */
class Basic implements CredentialsInterface
{
    /**
     * @var array
     */
    protected array $credentials = [];

    /**
     * Create a credential set with an API key and secret.
     *
     * @param $key
     * @param $secret
     */
    public function __construct($key, $secret, $accountType = 'prepaid')
    {
        $accountType = (string) $accountType;

        if (!in_array($accountType, ['prepaid', 'postpaid'], true)) {
            throw new InvalidArgumentException(
                'Invalid account type. Allowed values: prepaid, postpaid.'
            );
        }

        $this->credentials['api_key'] = (string) $key;
        $this->credentials['api_secret'] = (string) $secret;
        $this->credentials['accountType'] = $accountType;
    }

    /**
     * @noinspection MagicMethodsValidityInspection
     */
    public function __get($name)
    {
        return $this->credentials[$name];
    }

    public function asArray(): array
    {
        return $this->credentials;
    }
}