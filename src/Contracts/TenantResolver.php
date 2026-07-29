<?php

namespace Schoolpalm\MessageDelivery\Contracts;

interface TenantResolver
{
    public function resolve(): ?string;
}
