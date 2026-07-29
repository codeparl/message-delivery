<?php

namespace Schoolpalm\MessageDelivery\Contracts;

interface DeliveryLogger
{
    public function log(array $data): void;
}
