<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery;

use SchoolPalm\MessageDelivery\Builders\ChannelMessageBuilder;
use SchoolPalm\MessageDelivery\Builders\MultiChannelMessageBuilder;
use SchoolPalm\MessageDelivery\Context\MessageContext;

final class MessageDelivery
{
    /**
     * Current execution context.
     */
    private ?MessageContext $context;


    /**
     * Create a MessageDelivery instance.
     */
    public function __construct(
        ?MessageContext $context = null
    ) {
        $this->context = $context;
    }


    /**
     * Attach execution context.
     *
     * Context is supplied by application adapters
     * such as Module Bridge.
     *
     * Example:
     *
     * MessageDelivery::withContext([
     *     'tenant_id' => 1,
     *     'module' => 'finance'
     * ])
     * ->sms()
     * ->send();
     */
    public static function withContext(
        array|MessageContext $context
    ): self {

        if (is_array($context)) {
            $context = new MessageContext($context);
        }

        return new self($context);
    }


    /**
     * Create an SMS message builder.
     */
    public function sms(): ChannelMessageBuilder
    {
        return new ChannelMessageBuilder(
            channel: 'sms',
            context: $this->context
        );
    }


    /**
     * Create an email message builder.
     */
    public function email(): ChannelMessageBuilder
    {
        return new ChannelMessageBuilder(
            channel: 'email',
            context: $this->context
        );
    }


    /**
     * Create a push notification builder.
     */
    public function push(): ChannelMessageBuilder
    {
        return new ChannelMessageBuilder(
            channel: 'push',
            context: $this->context
        );
    }


    /**
     * Create a WhatsApp message builder.
     */
    public function whatsapp(): ChannelMessageBuilder
    {
        return new ChannelMessageBuilder(
            channel: 'whatsapp',
            context: $this->context
        );
    }


    /**
     * Create a multi-channel message builder.
     *
     * Example:
     *
     * MessageDelivery::withContext([
     *     'tenant_id'=>1
     * ])
     * ->channels([
     *     'sms',
     *     'email'
     * ])
     * ->send();
     */
    public function channels(
        array $channels
    ): MultiChannelMessageBuilder {

        return new MultiChannelMessageBuilder(
            channels: $channels,
            context: $this->context
        );
    }
}
