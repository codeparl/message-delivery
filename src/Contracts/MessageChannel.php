<?php

namespace Schoolpalm\MessageDelivery\Contracts;

interface MessageChannel
{
    public function send(array $message);
}
