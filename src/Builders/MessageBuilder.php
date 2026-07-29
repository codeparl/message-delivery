<?php

namespace Schoolpalm\MessageDelivery\Builders;

class MessageBuilder
{
    protected array $data = [];

    public function withData(array $data): self
    {
        $this->data = $data;
        return $this;
    }
}
