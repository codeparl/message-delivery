<?php

namespace Schoolpalm\MessageDelivery\Contracts;

interface QueueDispatcher
{
    public function dispatch(string $job, array $payload): void;
}
