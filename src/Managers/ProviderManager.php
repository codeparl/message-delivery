<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Managers;

use InvalidArgumentException;
use SchoolPalm\MessageDelivery\Contracts\MessageProvider;
use SchoolPalm\MessageDelivery\Contracts\TenantProviderSettings;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Registry\ProviderRegistry;

final class ProviderManager
{
    public function __construct(
        protected ProviderRegistry $providerRegistry,

        protected TenantProviderSettings $settings,
    ) {}


    /**
     * Resolve provider for message.
     *
     * Resolution order:
     *
     * 1. Explicit provider defined in message
     * 2. Tenant configured provider
     *
     * Provider configuration is always loaded
     * from TenantProviderSettings.
     */
    public function resolve(
        Message $message
    ): MessageProvider {

        $provider = $message->provider
            ?? $this->settings->providerFor(
                $message->channel
            );


        if ($provider === null) {

            throw new InvalidArgumentException(
                "No provider configured for channel [{$message->channel}]."
            );
        }


        if (! $this->settings->enabled(
            $message->channel,
            $provider
        )) {

            throw new InvalidArgumentException(
                "Provider [{$provider}] is disabled for channel [{$message->channel}]."
            );
        }


        $configuration = $this->settings
            ->configurationFor(
                $message->channel,
                $provider
            );


        return $this->providerRegistry->make(
            $message->channel,
            $provider,
            $configuration
        );
    }
}
