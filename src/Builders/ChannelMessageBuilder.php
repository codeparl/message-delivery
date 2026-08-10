<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Builders;

use Closure;
use DateInterval;
use DateTimeInterface;
use SchoolPalm\MessageDelivery\Managers\MessageManager;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Queue\QueueOptions;

final class ChannelMessageBuilder
{
    /**
     * @var array<int, mixed>
     */
    protected array $recipients = [];

    /**
     * Property/channel route mappings for DTOs, models, or plain arrays.
     * e.g. ['email' => 'emailAddress', 'sms' => 'mobile', 'in_app' => 'parentId']
     *
     * @var array<string, mixed>
     */
    protected array $routes = [];

    protected ?string $view = null;

    protected ?string $template = null;

    protected ?string $text = null;

    protected array $data = [];

    protected ?string $provider = null;

    protected ?string $priority = null;

    protected QueueOptionsBuilder $queueOptions;

    public function __construct(
        protected readonly string $channel,
        protected readonly array $context = [],
    ) {
        $this->queueOptions = new QueueOptionsBuilder();
    }

    /**
     * Set message recipients (accepts models, DTO objects, strings, or arrays).
     *
     * @param mixed $recipients
     * @return static
     */
    public function to(mixed $recipients): static
    {
        if (is_array($recipients)) {
            // Distinguish indexed list of recipients vs. associative array
            $this->recipients = array_is_list($recipients) ? $recipients : [$recipients];
        } else {
            $this->recipients = [$recipients];
        }

        return $this;
    }

    /**
     * Define channel or property routes mapping.
     *
     * @param array<string, mixed> $routes E.g. ['email' => 'emailAddress', 'sms' => 'mobile']
     * @return static
     */
    public function routes(array $routes): static
    {
        $this->routes = array_merge($this->routes, $routes);

        return $this;
    }

    /**
     * Set the explicit target or property mapping for this specific channel.
     *
     * @param mixed $target Property name on recipient object, Closure, or raw target value
     * @return static
     */
    public function route(mixed $target): static
    {
        $this->routes[$this->channel] = $target;

        return $this;
    }

    /**
     * Set payload/view data variables.
     *
     * @param array<string, mixed>|string $key
     * @param mixed $value
     * @return static
     */
    public function with(array|string $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Use a Laravel view template with optional view data.
     *
     * @param string $view View template name or namespace
     * @param array<string, mixed> $data View data variables
     * @return static
     */
    public function view(string $view, array $data = []): static
    {
        $this->view = $view;

        if (!empty($data)) {
            $this->with($data);
        }

        return $this;
    }

    /**
     * Use stored message template.
     */
    public function template(string $template): static
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Set raw message text.
     */
    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Set message title.
     */
    public function title(string $title): static
    {
        $this->data['title'] = $title;

        return $this;
    }

    /**
     * Set the email subject.
     */
    public function subject(string $subject): static
    {
        $this->data['subject'] = $subject;

        return $this;
    }

    /**
     * Select specific provider.
     */
    public function provider(string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Set message priority.
     */
    public function priority(string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Queue Options
    |--------------------------------------------------------------------------
    */

    public function delay(DateTimeInterface|DateInterval|int $delay): static
    {
        $this->queueOptions->delay($delay);

        return $this;
    }

    public function onQueue(string $queue): static
    {
        $this->queueOptions->onQueue($queue);

        return $this;
    }

    public function onConnection(string $connection): static
    {
        $this->queueOptions->onConnection($connection);

        return $this;
    }

    public function tries(int $tries): static
    {
        $this->queueOptions->tries($tries);

        return $this;
    }

    public function timeout(int $seconds): static
    {
        $this->queueOptions->timeout($seconds);

        return $this;
    }

    public function backoff(int|array $backoff): static
    {
        $this->queueOptions->backoff($backoff);

        return $this;
    }

    public function afterCommit(bool $value = true): static
    {
        $this->queueOptions->afterCommit($value);

        return $this;
    }

    /**
     * Advanced queue configuration.
     */
    public function queueOptions(callable $callback): static
    {
        $callback($this->queueOptions);

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Sending
    |--------------------------------------------------------------------------
    */

    /**
     * Send message.
     */
    public function send()
    {
        $message = $this->build();

        return app(MessageManager::class)
            ->send(
                $message,
                queued: $this->shouldQueue($message->queueOptions)
            );
    }

    /**
     * Send synchronously without using the queue, overriding any attached queue options.
     */
    public function sync()
    {
        return app(MessageManager::class)
            ->send(
                $this->build(),
                queued: false
            );
    }

    /**
     * Send through queue explicitly.
     */
    public function queue()
    {
        return app(MessageManager::class)
            ->send(
                $this->build(),
                queued: true
            );
    }

    /**
     * Send through queue explicitly.
     */
    public function dispatch()
    {
        return app(MessageManager::class)
            ->send(
                $this->build(),
                queued: true
            );
    }

    /**
     * Check if the resolved QueueOptions demand queued execution.
     */
    protected function shouldQueue(?QueueOptions $options): bool
    {
        if ($options === null) {
            return false;
        }

        return $options->hasDelay()
            || $options->hasQueue()
            || $options->hasConnection();
    }

    /**
     * Build message object, resolving recipient entities into scalar target strings.
     */
    protected function build(): Message
    {
        $resolvedRecipients = $this->resolveRecipients();

        $data = $this->data;
        if (!empty($this->routes)) {
            $data['routes'] = $this->routes;
        }

        return new Message(
            channel: $this->channel,
            recipients: $resolvedRecipients,
            view: $this->view,
            template: $this->template,
            text: $this->text,
            data: $data,
            provider: $this->provider,
            priority: $this->priority,
            context: $this->context,
            queueOptions: $this->queueOptions->build(),
        );
    }

    /**
     * Resolves raw recipient entities into an array of target address strings.
     *
     * @return array<int, string>
     */
    protected function resolveRecipients(): array
    {
        $resolved = [];

        foreach ($this->recipients as $recipient) {
            $target = $this->resolveRecipientTarget($recipient);

            if ($target === null) {
                continue;
            }

            if (is_array($target)) {
                foreach ($target as $subTarget) {
                    if (is_string($subTarget) || is_numeric($subTarget)) {
                        $resolved[] = (string) $subTarget;
                    }
                }
            } elseif (is_string($target) || is_numeric($target)) {
                $resolved[] = (string) $target;
            }
        }

        return array_values(array_unique($resolved));
    }

    /**
     * Resolves the target address for a single recipient entity.
     *
     * Priority:
     * 1. Direct scalar value (string / int)
     * 2. Explicit channel route mapping via routes() / route()
     * 3. Model/DTO routeNotificationFor($channel) method
     * 4. Channel fallback property/getter lookup (e.g. 'email', 'phone', 'user_id')
     *
     * @param mixed $recipient
     * @return mixed
     */
    protected function resolveRecipientTarget(mixed $recipient): mixed
    {
        if (is_string($recipient) || is_numeric($recipient)) {
            return $recipient;
        }

        // Check explicit routes passed for current channel
        if (isset($this->routes[$this->channel])) {
            $route = $this->routes[$this->channel];

            if ($route instanceof Closure) {
                return $route($recipient);
            }

            if (is_string($route) && (is_object($recipient) || is_array($recipient))) {
                $extracted = $this->extractValueFromRecipient($recipient, $route);
                if ($extracted !== null) {
                    return $extracted;
                }
            }

            if (is_string($route) || is_array($route)) {
                return $route;
            }
        }

        // Check routeNotificationFor method on Model/DTO
        if (is_object($recipient) && method_exists($recipient, 'routeNotificationFor')) {
            $target = $recipient->routeNotificationFor($this->channel);
            if ($target !== null) {
                return $target;
            }
        }

        // Channel-based fallback keys
        foreach ($this->getChannelFallbackKeys() as $key) {
            $value = $this->extractValueFromRecipient($recipient, $key);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Extract property or method value from Array or Object recipient.
     */
    protected function extractValueFromRecipient(mixed $recipient, string $key): mixed
    {
        if (is_array($recipient) && isset($recipient[$key])) {
            return $recipient[$key];
        }

        if (is_object($recipient)) {
            if (isset($recipient->{$key})) {
                return $recipient->{$key};
            }

            $getter = 'get' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $key)));
            if (method_exists($recipient, $getter)) {
                return $recipient->{$getter}();
            }

            if (method_exists($recipient, $key)) {
                return $recipient->{$key}();
            }
        }

        return null;
    }

    /**
     * Fallback property names for resolution based on active channel.
     *
     * @return array<int, string>
     */
    protected function getChannelFallbackKeys(): array
    {
        return match ($this->channel) {
            'email' => ['email', 'email_address', 'mail'],
            'sms', 'whatsapp' => ['phone', 'mobile', 'phone_number', 'mobile_number', 'msisdn'],
            'database', 'in_app' => ['id', 'user_id', 'recipient_id', 'uuid'],
            default => [$this->channel, 'target', 'address'],
        };
    }
}
