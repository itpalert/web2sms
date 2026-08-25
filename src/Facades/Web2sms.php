<?php

namespace ITPalert\Web2sms\Facades;

use Illuminate\Support\Facades\Facade;
use ITPalert\Web2sms\Contracts\Client;
use ITPalert\Web2sms\Testing\Web2smsFake;

/**
 * @method static \ITPalert\Web2sms\Responses\SendResponse send(\ITPalert\Web2sms\SMS $message)
 * @method static \ITPalert\Web2sms\Responses\StatusResponse get(string $id)
 * @method static \ITPalert\Web2sms\Responses\DeleteResponse delete(string $id)
 * @method static \ITPalert\Web2sms\Responses\BalanceResponse balance()
 *
 * @see \ITPalert\Web2sms\Client
 */
class Web2sms extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Client::class;
    }

    /**
     * Record messages instead of sending them, and hand back the recorder.
     *
     * Rarely needed under the testing environment, where the fake is already
     * the default. Its real use is getting hold of the recorder to assert on,
     * and switching a single test away from a driver set to `api`.
     */
    public static function fake(): Web2smsFake
    {
        $existing = static::isFake() ? static::getFacadeRoot() : null;

        if ($existing instanceof Web2smsFake) {
            return $existing;
        }

        static::swap($fake = new Web2smsFake());

        return $fake;
    }

    /** Whether messages are currently being recorded rather than sent. */
    public static function isFake(): bool
    {
        return static::isResolved() && static::getFacadeRoot() instanceof Web2smsFake;
    }

    protected static function isResolved(): bool
    {
        return isset(static::$resolvedInstance[static::getFacadeAccessor()])
            || static::getFacadeApplication()?->resolved(static::getFacadeAccessor());
    }
}
