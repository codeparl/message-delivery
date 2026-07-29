<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Queue;

use DateInterval;
use DateTimeInterface;

final class QueueOptions
{
    /**
     * Create queue dispatch options.
     *
     * These options are forwarded to the
     * schoolpalm/queued-jobs package when
     * dispatching queued message deliveries.
     */
    public function __construct(
        /**
         * Delay before the job is dispatched.
         */
        public readonly DateTimeInterface|DateInterval|int|null $delay = null,

        /**
         * Queue connection name.
         */
        public readonly ?string $connection = null,

        /**
         * Queue name.
         */
        public readonly ?string $queue = null,

        /**
         * Maximum number of attempts.
         */
        public readonly ?int $tries = null,

        /**
         * Job timeout in seconds.
         */
        public readonly ?int $timeout = null,

        /**
         * Backoff delay(s).
         *
         * Example:
         *
         * 60
         *
         * or
         *
         * [60, 120, 300]
         */
        public readonly int|array|null $backoff = null,

        /**
         * Dispatch only after the current
         * database transaction commits.
         */
        public readonly bool $afterCommit = false,

        /**
         * Queue middleware.
         */
        public readonly array $middleware = [],

        /**
         * Jobs to execute after this job.
         */
        public readonly array $chain = [],
    ) {}

    /**
     * Determine whether a delay is configured.
     */
    public function hasDelay(): bool
    {
        return $this->delay !== null;
    }

    /**
     * Determine whether a queue was specified.
     */
    public function hasQueue(): bool
    {
        return $this->queue !== null;
    }

    /**
     * Determine whether a connection was specified.
     */
    public function hasConnection(): bool
    {
        return $this->connection !== null;
    }

    /**
     * Determine whether retry attempts are configured.
     */
    public function hasTries(): bool
    {
        return $this->tries !== null;
    }

    /**
     * Determine whether timeout is configured.
     */
    public function hasTimeout(): bool
    {
        return $this->timeout !== null;
    }

    /**
     * Convert options to an array.
     */
    public function toArray(): array
    {
        return [
            'delay' => $this->delay,
            'connection' => $this->connection,
            'queue' => $this->queue,
            'tries' => $this->tries,
            'timeout' => $this->timeout,
            'backoff' => $this->backoff,
            'after_commit' => $this->afterCommit,
            'middleware' => $this->middleware,
            'chain' => $this->chain,
        ];
    }
}
