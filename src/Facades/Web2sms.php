<?php

namespace ITPalert\Web2sms\Facades;
 
use Illuminate\Support\Facades\Facade;
 
use ITPalert\Web2sms\Web2sms as Web2smsClass;

class Web2sms extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return Web2smsClass::class;
    }
}