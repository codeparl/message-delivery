<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Engine;

use SchoolPalm\MessageDelivery\MessageDelivery;
use SchoolPalm\MessageDelivery\Messages\MultiChannelResult;
use SchoolPalm\MessageDelivery\Notification\Contracts\ChannelResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\EventResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\LanguageResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\NotificationEngine as NotificationEngineContract;
use SchoolPalm\MessageDelivery\Notification\Contracts\PreferenceResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\PriorityResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\RecipientResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\RetryResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\ScheduleResolver;
use SchoolPalm\MessageDelivery\Notification\Contracts\TemplateResolver;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationDecision;
use SchoolPalm\MessageDelivery\Notification\DTO\NotificationEvent;
use SchoolPalm\MessageDelivery\Notification\Support\NotificationResult;
use SchoolPalm\MessageDelivery\Queue\QueueOptions;

/**
 * Notification orchestration engine.
 *
 * The engine coordinates resolvers and delegates message delivery
 * directly to the MessageDelivery package.
 */
final class NotificationEngine implements NotificationEngineContract
{
    /**
     * Create the notification engine.
     */
    public function __construct(
        protected EventResolver $eventResolver,
        protected RecipientResolver $recipientResolver,
        protected PreferenceResolver $preferenceResolver,
        protected ChannelResolver $channelResolver,
        protected LanguageResolver $languageResolver,
        protected TemplateResolver $templateResolver,
        protected PriorityResolver $priorityResolver,
        protected ScheduleResolver $scheduleResolver,
        protected RetryResolver $retryResolver,
        protected MessageDelivery $delivery,
        protected array $config = [],
    ) {}

    /**
     * Dispatch a notification event.
     *
     * Orchestrates the resolvers and delegates delivery to MessageDelivery.
     */
    public function dispatch(
        NotificationEvent $event
    ): NotificationResult {

        /*
        |--------------------------------------------------------------------------
        | 1. Resolve event metadata
        |--------------------------------------------------------------------------
        */

        $metadata = $this->eventResolver->resolve($event);

        $event = new NotificationEvent(
            event: $event->event,
            data: $event->data,
            context: $event->context,
            metadata: array_merge($event->metadata, $metadata),
            requestedChannels: $event->requestedChannels,
            requestedLanguage: $event->requestedLanguage,
            requestedPriority: $event->requestedPriority,
            requestedTemplate: $event->requestedTemplate,
        );

        /*
        |--------------------------------------------------------------------------
        | 2. Resolve recipients
        |--------------------------------------------------------------------------
        */

        $recipients = $this->recipientResolver
            ->resolve($event)
            ->all();

        /*
        |--------------------------------------------------------------------------
        | 3. Skip when no recipients
        |--------------------------------------------------------------------------
        */

        if (empty($recipients)) {
            return NotificationResult::skipped(
                event: $event,
                reason: 'No recipients resolved.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Resolve preferences
        |--------------------------------------------------------------------------
        */

        $preferences = $this->preferenceResolver->resolve($event);

        /*
        |--------------------------------------------------------------------------
        | 5. Resolve channels
        |--------------------------------------------------------------------------
        */

        $channels = $this->channelResolver->resolve(
            $event,
            $preferences
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Skip when no channels
        |--------------------------------------------------------------------------
        */

        if (empty($channels)) {
            return NotificationResult::skipped(
                event: $event,
                reason: 'No channels resolved.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Resolve language
        |--------------------------------------------------------------------------
        */

        $language = $this->languageResolver->resolve($event)
            ?? $this->config['default_language']
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | 8. Resolve template
        |--------------------------------------------------------------------------
        */

        $template = $this->templateResolver->resolve(
            $event,
            $channels,
            $language
        );

        /*
        |--------------------------------------------------------------------------
        | 9. Resolve priority
        |--------------------------------------------------------------------------
        */

        $priority = $this->priorityResolver->resolve($event)
            ?? $this->config['default_priority']
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | 10. Resolve schedule
        |--------------------------------------------------------------------------
        */

        $schedule = $this->scheduleResolver->resolve($event);

        /*
        |--------------------------------------------------------------------------
        | 11. Resolve retry policy
        |--------------------------------------------------------------------------
        */

        $retryPolicy = $this->retryResolver->resolve($event);

        /*
        |--------------------------------------------------------------------------
        | Build decision
        |--------------------------------------------------------------------------
        */

        $decision = new NotificationDecision(
            channels: $channels,
            recipients: $recipients,
            data: $event->data,
            template: $template,
            language: $language,
            priority: $priority,
            retryPolicy: $retryPolicy,
            schedule: $schedule,
            preferences: $preferences,
        );

        /*
        |--------------------------------------------------------------------------
        | 12. Delegate delivery directly to MessageDelivery
        |--------------------------------------------------------------------------
        */

        $multiChannelResult = $this->deliverDirect($event, $decision);

        $deliveryData = method_exists($multiChannelResult, 'toArray')
            ? $multiChannelResult->toArray()
            : (method_exists($multiChannelResult, 'all') ? $multiChannelResult->all() : (array) $multiChannelResult);

        $delay = $schedule ?? $event->metadata['scheduled_at'] ?? $event->metadata['delay'] ?? null;
        $queueOptions = $event->metadata['queue_options'] ?? null;

        // If a delay or explicit queue option was passed, report queued status
        if ($delay !== null || ($queueOptions instanceof QueueOptions && $this->shouldQueue($queueOptions))) {
            return NotificationResult::queued(
                event: $event,
                decision: $decision,
                delivery: $deliveryData,
            );
        }

        return NotificationResult::dispatched(
            event: $event,
            decision: $decision,
            delivery: $deliveryData,
        );
    }

    /**
     * Determine whether queue configuration requires async execution.
     */
    protected function shouldQueue(QueueOptions $options): bool
    {
        return $options->delay !== null
            || $options->queue !== null
            || $options->connection !== null;
    }

    /**
     * Build messages from the decision and delegate directly to MessageDelivery.
     */
    public function deliverDirect(
        NotificationEvent $event,
        NotificationDecision $decision
    ): MultiChannelResult {

        $builder = $this->delivery->channels($decision->channels);

        // Pass resolved recipients
        $builder->to($decision->recipients);

        // Filter out 'recipients' key from payload data
        $payloadData = $decision->data;
        unset($payloadData['recipients']);

        if (! empty($payloadData)) {
            $builder->with($payloadData);
        }

        if (! empty($event->context)) {
            $builder->context($event->context);
        }

        // ------------------------------------------------------------------
        // RESOLVE VIEW VS TEXT CONTENT
        // ------------------------------------------------------------------
        $explicitView = $event->metadata['view']
            ?? $payloadData['view']
            ?? $event->data['view']
            ?? null;

        $explicitText = $event->metadata['text']
            ?? $payloadData['text']
            ?? $event->data['text']
            ?? null;

        $explicitTitle = $event->metadata['title']
            ?? $payloadData['title']
            ?? $event->data['title']
            ?? null;

        if ($explicitTitle !== null) {
            $builder->title($explicitTitle);
        }

        if ($explicitView !== null) {
            $builder->view($explicitView);
        }

        if ($explicitText !== null) {
            $builder->text($explicitText);
        }

        // Apply template rendering if no explicit view/text overrides were provided
        if ($explicitView === null && $explicitText === null && $decision->template !== null) {
            if ($decision->template->hasSubject()) {
                $builder->with([
                    'subject' => $decision->template->subject,
                ]);
            }

            $builder->text(
                $decision->template->render($payloadData)
            );
        }

        // ------------------------------------------------------------------
        // PASS QUEUE AND SCHEDULING OPTIONS TO MESSAGE DELIVERY
        // ------------------------------------------------------------------
        $delay = $decision->schedule ?? $event->metadata['scheduled_at'] ?? $event->metadata['delay'] ?? null;
        if ($delay !== null) {
            $builder->delay($delay);
        }

        $queueOptions = $event->metadata['queue_options'] ?? null;

        // Resolve queue name: QueueOptions -> Metadata -> Config -> Fallback 'default'
        $targetQueue = $queueOptions?->queue
            ?? $event->metadata['queue']
            ?? $this->config['default_queue']
            ?? 'default';

        $builder->onQueue($targetQueue);

        if ($queueOptions?->connection !== null) {
            $builder->onConnection($queueOptions->connection);
        }

        // Apply priority and retry policies
        if ($decision->priority !== null) {
            $builder->priority($decision->priority);
        }

        if ($decision->retryPolicy !== null) {
            $policy = $decision->retryPolicy;

            if ($policy->tries !== null) {
                $builder->tries($policy->tries);
            }
            if ($policy->timeout !== null) {
                $builder->timeout($policy->timeout);
            }
            if ($policy->backoff !== null) {
                $builder->backoff($policy->backoff);
            }
            if ($policy->queue !== null) {
                $builder->onQueue($policy->queue);
            }
            if ($policy->connection !== null) {
                $builder->onConnection($policy->connection);
            }
        }

        return $builder->send();
    }
}
