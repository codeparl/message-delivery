<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Notification\Engine;

use SchoolPalm\MessageDelivery\MessageDelivery;
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

/**
 * Notification orchestration engine.
 *
 * The engine coordinates resolvers and delegates message delivery
 * to the existing MessageDelivery package. It is orchestration-only
 * and MUST NOT contain business rules.
 *
 * Flow:
 *
 * 1.  Resolve event metadata
 * 2.  Resolve recipients
 * 3.  Skip when no recipients
 * 4.  Resolve preferences
 * 5.  Resolve channels
 * 6.  Skip when no channels
 * 7.  Resolve language
 * 8.  Resolve template
 * 9.  Resolve priority
 * 10. Resolve schedule
 * 11. Resolve retry policy
 * 12. Build messages
 * 13. Delegate to MessageDelivery
 */
final class NotificationEngine implements NotificationEngineContract
{
    /**
     * Create the notification engine.
     *
     * @param  EventResolver      $eventResolver
     * @param  RecipientResolver  $recipientResolver
     * @param  PreferenceResolver $preferenceResolver
     * @param  ChannelResolver    $channelResolver
     * @param  LanguageResolver   $languageResolver
     * @param  TemplateResolver   $templateResolver
     * @param  PriorityResolver   $priorityResolver
     * @param  ScheduleResolver   $scheduleResolver
     * @param  RetryResolver      $retryResolver
     * @param  MessageDelivery    $delivery
     * @param  array<string, mixed> $config
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
     * Orchestrates the resolvers and delegates delivery to the
     * MessageDelivery package.
     */
    public function dispatch(
        NotificationEvent $event
    ): NotificationResult {

        /*
        |--------------------------------------------------------------------------
        | 1. Resolve event metadata
        |--------------------------------------------------------------------------
        */

        $metadata = $this->eventResolver->resolve(
            $event
        );

        $event = new NotificationEvent(
            event: $event->event,

            data: $event->data,

            context: $event->context,

            metadata: array_merge(
                $event->metadata,
                $metadata
            ),

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

        $preferences = $this->preferenceResolver
            ->resolve($event);


        /*
        |--------------------------------------------------------------------------
        | 5. Resolve channels
        |--------------------------------------------------------------------------
        */

        $channels = $this->channelResolver
            ->resolve(
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

        $language = $this->languageResolver
            ->resolve($event)
            ?? $this->config['default_language']
            ?? null;


        /*
        |--------------------------------------------------------------------------
        | 8. Resolve template
        |--------------------------------------------------------------------------
        */

        $template = $this->templateResolver
            ->resolve(
                $event,
                $channels,
                $language
            );


        /*
        |--------------------------------------------------------------------------
        | 9. Resolve priority
        |--------------------------------------------------------------------------
        */

        $priority = $this->priorityResolver
            ->resolve($event)
            ?? $this->config['default_priority']
            ?? null;


        /*
        |--------------------------------------------------------------------------
        | 10. Resolve schedule
        |--------------------------------------------------------------------------
        */

        $schedule = $this->scheduleResolver
            ->resolve($event);


        /*
        |--------------------------------------------------------------------------
        | 11. Resolve retry policy
        |--------------------------------------------------------------------------
        */

        $retryPolicy = $this->retryResolver
            ->resolve($event);


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
        | 12/13. Build messages and delegate to MessageDelivery
        |--------------------------------------------------------------------------
        */

        $delivery = $this->deliver(
            $event,
            $decision
        );


        return NotificationResult::dispatched(
            event: $event,
            decision: $decision,
            delivery: $delivery,
        );
    }


    /**
     * Build messages from the decision and delegate to MessageDelivery.
     *
     * @return \SchoolPalm\MessageDelivery\Messages\MultiChannelResult
     */
    protected function deliver(
        NotificationEvent $event,
        NotificationDecision $decision
    ): \SchoolPalm\MessageDelivery\Messages\MultiChannelResult {

        $builder = $this->delivery->channels(
            $decision->channels
        );

        $builder->to(
            $decision->recipients
        );

        /*
        |--------------------------------------------------------------------------
        | Template data cleanliness
        |--------------------------------------------------------------------------
        |
        | The `recipients` payload may contain Eloquent models or serialized
        | recipient collections. These are delivery concerns, not template
        | variables, and must not pollute template rendering (which can crash
        | on object string-conversion or array-serialization inside queued jobs).
        |
        */

        $templateData = $decision->data;

        unset($templateData['recipients']);

        if (! empty($templateData)) {
            $builder->with($templateData);
        }

        if (! empty($event->context)) {
            $builder->context($event->context);
        }

        if ($decision->template !== null) {

            if ($decision->template->hasSubject()) {
                $builder->with([
                    'subject' => $decision->template->subject,
                ]);
            }

            $builder->text(
                $decision->template->render(
                    $templateData
                )
            );
        }

        if ($decision->priority !== null) {
            $builder->priority($decision->priority);
        }

        if ($decision->schedule !== null) {
            $builder->delay($decision->schedule);
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
