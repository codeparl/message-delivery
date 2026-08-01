<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\DTO;

/**
 * Immutable retry policy for queued notification delivery.
 */
final class RetryPolicy
{
    /**
     * Create a retry policy.
     *
     * @param  int|null           $tries   Maximum attempts
     * @param  int|null           $timeout Job timeout in seconds
     * @param  int|array|null     $backoff Backoff delay(s)
     * @param  string|null        $queue   Queue name
     * @param  string|null        $connection Queue connection
     */
    public function __construct(
        public readonly ?int $tries = null,

        public readonly ?int $timeout = null,

        public readonly int|array|null $backoff = null,

        public readonly ?string $queue = null,

        public readonly ?string $connection = null,
    ) {}


    /**
     * Convert the policy to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tries' => $this->tries,
            'timeout' => $this->timeout,
            'backoff' => $this->backoff,
            'queue' => $this->queue,
            'connection' => $this->connection,
        ];
    }
}

