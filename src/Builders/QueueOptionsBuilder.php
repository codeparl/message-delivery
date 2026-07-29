<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Builders;

use DateInterval;
use DateTimeInterface;
use SchoolPalm\MessageDelivery\Queue\QueueOptions;

final class QueueOptionsBuilder
{
    protected DateTimeInterface|DateInterval|int|null $delay = null;

    protected ?string $connection = null;

    protected ?string $queue = null;

    protected ?int $tries = null;

    protected ?int $timeout = null;

    protected int|array|null $backoff = null;

    protected bool $afterCommit = false;


    /**
     * Set queue delay.
     */
    public function delay(
        DateTimeInterface|DateInterval|int $delay
    ): static {

        $this->delay = $delay;

        return $this;
    }


    /**
     * Set queue connection.
     */
    public function onConnection(
        string $connection
    ): static {

        $this->connection = $connection;

        return $this;
    }


    /**
     * Set queue name.
     */
    public function onQueue(
        string $queue
    ): static {

        $this->queue = $queue;

        return $this;
    }


    /**
     * Set maximum retry attempts.
     */
    public function tries(
        int $tries
    ): static {

        $this->tries = $tries;

        return $this;
    }


    /**
     * Set job timeout.
     */
    public function timeout(
        int $seconds
    ): static {

        $this->timeout = $seconds;

        return $this;
    }


    /**
     * Set retry backoff.
     *
     * Example:
     *
     * ->backoff(60)
     *
     * or
     *
     * ->backoff([60,120,300])
     */
    public function backoff(
        int|array $backoff
    ): static {

        $this->backoff = $backoff;

        return $this;
    }


    /**
     * Dispatch after database commit.
     */
    public function afterCommit(
        bool $value = true
    ): static {

        $this->afterCommit = $value;

        return $this;
    }


    /**
     * Build immutable queue options.
     */
    public function build(): QueueOptions
    {
        return new QueueOptions(
            delay: $this->delay,

            connection: $this->connection,

            queue: $this->queue,

            tries: $this->tries,

            timeout: $this->timeout,

            backoff: $this->backoff,

            afterCommit: $this->afterCommit,
        );
    }
}
