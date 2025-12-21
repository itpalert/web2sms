<?php

namespace ITPalert\Web2sms\Facades;

use Illuminate\Support\Facades\Facade;
use ITPalert\Web2sms\Client;

class Web2sms extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Client::class;
    }
}
