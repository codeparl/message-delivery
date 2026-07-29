<?php

namespace Schoolpalm\MessageDelivery\Contracts;

interface ProviderRegistry
{
    public function register(string $name, MessageProvider $provider);
}
