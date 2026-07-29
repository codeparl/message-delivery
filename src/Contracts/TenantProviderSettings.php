<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Contracts;

interface TenantProviderSettings
{
    /**
     * Get configured provider for a channel.
     *
     * Example:
     *
     * sms      => egosms
     * email    => ses
     * whatsapp => meta
     */
    public function providerFor(
        string $channel
    ): ?string;


    /**
     * Get provider configuration.
     *
     * Example:
     *
     * [
     *     'api_key' => 'xxxx',
     *     'username' => 'school'
     * ]
     */
    public function configurationFor(
        string $channel,
        string $provider
    ): array;


    /**
     * Check whether a provider is enabled.
     */
    public function enabled(
        string $channel,
        string $provider
    ): bool;
}
