<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Contracts;

use SchoolPalm\MessageDelivery\Messages\DeliveryResult;
use SchoolPalm\MessageDelivery\Messages\Message;
use SchoolPalm\MessageDelivery\Models\MessageDelivery;

/**
 * Contract for persisting message delivery records.
 *
 * This interface abstracts delivery persistence so that
 * the MessageManager can record delivery lifecycle events
 * (queued, sent, failed) without depending on the underlying
 * storage implementation.
 *
 * The default implementation is DatabaseDeliveryRecorder which
 * stores records in the message_deliveries table.
 *
 * Who uses it:
 * - MessageManager calls recordQueued/recordSent/recordFailed
 *   at each stage of the delivery lifecycle.
 * - Future queue job handlers may also call recordSent/recordFailed.
 *
 * What it should NOT do:
 * - NOT send messages or resolve providers
 * - NOT handle queue dispatch
 * - NOT perform logging (AppLogger is used separately)
 * - NOT implement business logic beyond persistence
 */
interface DeliveryRecorder
{
    /**
     * Record a message as queued for delivery.
     *
     * Called before dispatching a message to the queue.
     * Creates a delivery record with status 'queued' and
     * sets queued_at timestamp.
     *
     * @param  Message  $message  The message being queued
     * @return MessageDelivery     The created delivery record
     */
    public function recordQueued(
        Message $message
    ): MessageDelivery;


    /**
     * Record a successful delivery.
     *
     * Updates the existing delivery record with status 'sent',
     * the provider name, provider message ID, and sets sent_at
     * timestamp.
     *
     * @param  Message         $message  The message that was sent
     * @param  DeliveryResult  $result   The delivery result from the provider
     * @return MessageDelivery            The updated delivery record
     */
    public function recordSent(
        Message $message,
        DeliveryResult $result
    ): MessageDelivery;


    /**
     * Record a failed delivery.
     *
     * Updates the existing delivery record with status 'failed',
     * the error message, and optionally the provider name.
     *
     * @param  Message         $message  The message that failed
     * @param  DeliveryResult  $result   The delivery result with error details
     * @return MessageDelivery            The updated delivery record
     */
    public function recordFailed(
        Message $message,
        DeliveryResult $result
    ): MessageDelivery;
}
