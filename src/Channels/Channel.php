<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Channels;

use SchoolPalm\MessageDelivery\Contracts\MessageChannel;

abstract class Channel implements MessageChannel
{
    /**
     * Channel name.
     */
    abstract public function name(): string;
}
