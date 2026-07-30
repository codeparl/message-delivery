<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Builders;

use SchoolPalm\MessageDelivery\Messages\MultiChannelResult;
use SchoolPalm\MessageDelivery\Messages\DeliveryResult;

/**
 * Allows sending the same communication through multiple channels.
 *
 * Who consumes it:
 * - Module developers across SchoolPalm modules
 *   (Finance, Admissions, Communications, etc.)
 *
 * Example:
 *
 * MessageDelivery::multi()
 *     ->channels(['email', 'sms'])
 *     ->to($parent)
 *     ->text('Your child has been admitted')
 *     ->send();
 *
 * Responsibility:
 * - Manage multiple ChannelMessageBuilder instances
 * - Share common message data (recipients, text, etc.)
 * - Dispatch messages to multiple channels
 * - Aggregate results into MultiChannelResult
 *
 * What it does NOT handle:
 * - Does NOT send messages directly (delegates to ChannelMessageBuilder)
 * - Does NOT know about providers or APIs
 * - Does NOT know email/SMS implementation details
 * - Does NOT handle channel-specific logic
 */
final class MultiChannelMessageBuilder
{
    protected array $channels = [];

    protected array $recipients = [];

    protected ?string $view = null;

    protected ?string $template = null;

    protected ?string $text = null;

    protected array $data = [];

    protected ?string $provider = null;

    protected ?string $priority = null;

    /**
     * Create a MultiChannelMessageBuilder instance.
     *
     * @param array $channels  List of channel names (e.g. ['email', 'sms'])
     * @param array $context   Execution context from MessageDelivery::withContext()
     */
    public function __construct(
        array $channels = [],
        protected readonly array $context = [],
    ) {
        $this->channels = $channels;
    }

    /**
     * Set the channels to send through.
     *
     * @param  array $channels  List of channel identifiers
     * @return static
     */
    public function channels(
        array $channels
    ): static {
        $this->channels = $channels;

        return $this;
    }

    /**
     * Set message recipients.
     *
     * @param  string|array $recipients
     * @return static
     */
    public function to(
        string|array $recipients
    ): static {
        $this->recipients = is_array($recipients)
            ? $recipients
            : [$recipients];

        return $this;
    }

    /**
     * Set raw message text.
     *
     * @param  string $text
     * @return static
     */
    public function text(
        string $text
    ): static {
        $this->text = $text;

        return $this;
    }

    /**
     * Use a Laravel view template.
     *
     * @param  string $view
     * @return static
     */
    public function view(
        string $view
    ): static {
        $this->view = $view;

        return $this;
    }

    /**
     * Use stored message template.
     *
     * @param  string $template
     * @return static
     */
    public function template(
        string $template
    ): static {
        $this->template = $template;

        return $this;
    }

    /**
     * Add template variables.
     *
     * @param  array $data
     * @return static
     */
    public function with(
        array $data
    ): static {
        $this->data = array_merge(
            $this->data,
            $data
        );

        return $this;
    }

    /**
     * Select specific provider.
     *
     * @param  string $provider
     * @return static
     */
    public function provider(
        string $provider
    ): static {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Set message priority.
     *
     * @param  string $priority
     * @return static
     */
    public function priority(
        string $priority
    ): static {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Send immediately through all channels.
     *
     * Dispatches to each channel sequentially.
     * One failed channel does NOT stop others.
     *
     * @return MultiChannelResult
     */
    public function send(): MultiChannelResult
    {
        $multiResult = new MultiChannelResult();

        foreach ($this->channels as $channel) {
            try {
                $builder = $this->createChannelBuilder($channel);
                $result = $builder->send();
            } catch (\Throwable $e) {
                $result = DeliveryResult::failure(
                    error: $e->getMessage(),
                    provider: null,
                    metadata: ['exception' => get_class($e)]
                );
            }

            $multiResult->add($channel, $result);
        }

        return $multiResult;
    }

    /**
     * Send through queue for all channels.
     *
     * Dispatches each channel's message to the queue.
     * One failed channel does NOT stop others.
     *
     * @return MultiChannelResult
     */
    public function queue(): MultiChannelResult
    {
        $multiResult = new MultiChannelResult();

        foreach ($this->channels as $channel) {
            try {
                $builder = $this->createChannelBuilder($channel);
                $result = $builder->queue();
            } catch (\Throwable $e) {
                $result = DeliveryResult::failure(
                    error: $e->getMessage(),
                    provider: null,
                    metadata: ['exception' => get_class($e)]
                );
            }

            $multiResult->add($channel, $result);
        }

        return $multiResult;
    }

    /**
     * Create a ChannelMessageBuilder for a given channel
     * with all shared values applied.
     *
     * @param  string $channel  Channel name
     * @return ChannelMessageBuilder
     */
    private function createChannelBuilder(
        string $channel
    ): ChannelMessageBuilder {
        $builder = new ChannelMessageBuilder(
            channel: $channel,
            context: $this->context,
        );

        if (! empty($this->recipients)) {
            $builder->to($this->recipients);
        }

        if ($this->text !== null) {
            $builder->text($this->text);
        }

        if ($this->view !== null) {
            $builder->view($this->view);
        }

        if ($this->template !== null) {
            $builder->template($this->template);
        }

        if (! empty($this->data)) {
            $builder->with($this->data);
        }

        if ($this->provider !== null) {
            $builder->provider($this->provider);
        }

        if ($this->priority !== null) {
            $builder->priority($this->priority);
        }

        return $builder;
    }
}

