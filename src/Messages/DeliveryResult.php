<?php

namespace Schoolpalm\MessageDelivery\Messages;

class DeliveryResult
{
    public bool $success = false;
    public ?string $provider = null;
    public array $meta = [];
}
