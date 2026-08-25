<?php

namespace ITPalert\Web2sms\Tests\Fixtures;

use ITPalert\Web2sms\Contracts\Client;
use ITPalert\Web2sms\SMS;

/**
 * Stands in for a downstream consumer of this package.
 *
 * Shaped like the notification channel and like application services: it takes
 * the contract through the constructor and never knows whether what it got
 * sends or records. That is the whole point of the contract, and it is what
 * lets a consumer's test suite fake this package without touching its own code.
 */
class ReminderService
{
    public function __construct(private Client $client)
    {
    }

    public function remind(string $to, string $message): ?string
    {
        return $this->client->send(new SMS($to, 'TEST', $message, 'text'))->getMessageId();
    }
}
