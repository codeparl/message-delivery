<?php

namespace Schoolpalm\MessageDelivery\Contracts;

interface RateLimiter
{
    public function allow(string $key): bool;
}
