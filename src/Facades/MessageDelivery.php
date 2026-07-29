<?php

namespace Schoolpalm\MessageDelivery\Facades;

use Illuminate\Support\Facades\Facade;

class MessageDelivery extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'message-delivery';
    }
}
