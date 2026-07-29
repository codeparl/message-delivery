<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Registry;

use InvalidArgumentException;
use SchoolPalm\MessageDelivery\Contracts\MessageChannel;

final class ChannelRegistry
{
    /**
     * Registered channels.
     *
     * @var array<string, MessageChannel>
     */
    protected array $channels = [];


    /**
     * Register a channel.
     */
    public function register(
        MessageChannel $channel
    ): void {

        $this->channels[$channel->name()] = $channel;
    }


    /**
     * Determine if channel exists.
     */
    public function has(
        string $name
    ): bool {

        return isset(
            $this->channels[$name]
        );
    }


    /**
     * Resolve channel.
     *
     * @throws InvalidArgumentException
     */
    public function resolve(
        string $name
    ): MessageChannel {

        if (! $this->has($name)) {

            throw new InvalidArgumentException(
                "Message channel [{$name}] is not registered."
            );
        }


        return $this->channels[$name];
    }


    /**
     * Get all registered channels.
     *
     * @return array<string, MessageChannel>
     */
    public function all(): array
    {
        return $this->channels;
    }


    /**
     * Remove a registered channel.
     */
    public function forget(
        string $name
    ): void {

        unset(
            $this->channels[$name]
        );
    }
}
