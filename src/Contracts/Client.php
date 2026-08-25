<?php

namespace ITPalert\Web2sms\Contracts;

use ITPalert\Web2sms\Responses\BalanceResponse;
use ITPalert\Web2sms\Responses\DeleteResponse;
use ITPalert\Web2sms\Responses\SendResponse;
use ITPalert\Web2sms\Responses\StatusResponse;
use ITPalert\Web2sms\SMS;

/**
 * What callers may ask of a Web2sms client.
 *
 * Exists so something other than the live API can stand behind the facade and
 * the notification channel. Deliberately excludes the HTTP plumbing
 * (setHttpClient, the endpoint URLs, the signing): those belong to the
 * concrete implementation that actually dials out, and a test double has no
 * business pretending to have them.
 */
interface Client
{
    public function send(SMS $message): SendResponse;

    public function get(string $id): StatusResponse;

    public function delete(string $id): DeleteResponse;

    public function balance(): BalanceResponse;
}
