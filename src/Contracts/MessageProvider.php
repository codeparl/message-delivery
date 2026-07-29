<?php

namespace Schoolpalm\MessageDelivery\Contracts;

interface MessageProvider
{
    public function send(array $payload);
}
