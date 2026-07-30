<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Providers\Email\Laravel;

use Illuminate\Mail\Mailable;
use SchoolPalm\MessageDelivery\Messages\Message;

/**
 * Mailable class that bridges the MessageDelivery Message
 * object to Laravel Mail.
 *
 * This class extends Illuminate\Mail\Mailable and is used
 * by LaravelMailProvider to send emails through Laravel's
 * mail system.
 *
 * It maps the Message object properties to the Mailable:
 * - recipients -> to()
 * - subject -> subject property (from data array or default)
 * - text -> html content
 * - view -> view template (if provided)
 * - data -> view variables
 * - data.cc -> cc()
 * - data.bcc -> bcc()
 * - data.reply_to -> replyTo()
 * - data.attachments -> attach() / attachData()
 *
 * Who uses it:
 * - LaravelMailProvider creates an instance of this class
 *   and passes it to Mail::mailer()->send()
 *
 * What it should NOT do:
 * - NOT send emails directly
 * - NOT resolve configuration
 * - NOT handle delivery results
 * - NOT modify the original Message object
 */
final class LaravelMailMessage extends Mailable
{
    /**
     * Create a new mailable instance.
     *
     * @param  Message  $message  The MessageDelivery message to convert
     */
    public function __construct(
        protected readonly Message $message
    ) {
        //
    }


    /**
     * Build the message.
     *
     * Configures the mailable with all content from the
     * MessageDelivery Message object.
     *
     * Sets:
     * - Subject from $message->data['subject'] or data['title']
     * - To recipients from $message->recipients
     * - HTML content from view or $message->text
     * - Plain text from data['plain_text']
     * - CC from data['cc']
     * - BCC from data['bcc']
     * - Reply-To from data['reply_to']
     * - Attachments from data['attachments']
     *
     * @return $this
     */
    public function build(): self
    {
        /*
        |--------------------------------------------------------------------------
        | Subject
        |--------------------------------------------------------------------------
        |
        | Subject is extracted from the message data array.
        | It can be set via the email builder's ->with(['subject' => '...']).
        |
        */

        $subject = $this->message->data['subject']
            ?? $this->message->data['title']
            ?? 'No Subject';

        $this->subject($subject);


        /*
        |--------------------------------------------------------------------------
        | Recipients (To)
        |--------------------------------------------------------------------------
        |
        | Message recipients array can contain:
        |
        | - Simple email strings: ['user@example.com']
        | - Associative arrays with name: [['email' => '...', 'name' => '...']]
        | - Mixed format
        |
        */

        foreach ($this->message->recipients as $recipient) {

            if (is_string($recipient)) {

                $this->to($recipient);
            } elseif (is_array($recipient)) {

                $email = $recipient['email']
                    ?? $recipient['address']
                    ?? null;

                $name = $recipient['name']
                    ?? $recipient['full_name']
                    ?? '';

                if ($email !== null) {

                    $this->to($email, $name);
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CC Recipients
        |--------------------------------------------------------------------------
        */

        if (! empty($this->message->data['cc'])) {

            foreach ((array) $this->message->data['cc'] as $cc) {

                if (is_string($cc)) {

                    $this->cc($cc);
                } elseif (is_array($cc)) {

                    $this->cc(
                        $cc['email'] ?? $cc['address'],
                        $cc['name'] ?? ''
                    );
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | BCC Recipients
        |--------------------------------------------------------------------------
        */

        if (! empty($this->message->data['bcc'])) {

            foreach ((array) $this->message->data['bcc'] as $bcc) {

                if (is_string($bcc)) {

                    $this->bcc($bcc);
                } elseif (is_array($bcc)) {

                    $this->bcc(
                        $bcc['email'] ?? $bcc['address'],
                        $bcc['name'] ?? ''
                    );
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Reply-To
        |--------------------------------------------------------------------------
        */

        if (! empty($this->message->data['reply_to'])) {

            $replyTo = $this->message->data['reply_to'];

            if (is_string($replyTo)) {

                $this->replyTo($replyTo);
            } elseif (is_array($replyTo)) {

                $this->replyTo(
                    $replyTo['email'] ?? $replyTo['address'],
                    $replyTo['name'] ?? ''
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Content
        |--------------------------------------------------------------------------
        |
        | If a view template is set, use it.
        | Otherwise, use the raw text content as HTML.
        |
        */

        if ($this->message->hasView()) {

            $this->view(
                $this->message->view,
                $this->message->data
            );
        } elseif ($this->message->hasText()) {

            $this->html(
                $this->message->text
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Plain Text
        |--------------------------------------------------------------------------
        |
        | Optional plain text version for email clients
        | that do not support HTML.
        |
        */

        if (! empty($this->message->data['plain_text'])) {

            $this->text(
                $this->message->data['plain_text']
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Attachments
        |--------------------------------------------------------------------------
        |
        | Support file attachments passed through the
        | message data array.
        |
        | Format:
        |
        | 'attachments' => [
        |     ['file' => '/path/to/file.pdf', 'options' => [...]],
        |     ['data' => $binaryData, 'name' => 'report.pdf', 'options' => [...]],
        | ]
        |
        */

        if (! empty($this->message->data['attachments'])) {

            foreach ($this->message->data['attachments'] as $attachment) {

                if (isset($attachment['file'])) {

                    $this->attach(
                        $attachment['file'],
                        $attachment['options'] ?? []
                    );
                } elseif (isset($attachment['data'])) {

                    $this->attachData(
                        $attachment['data'],
                        $attachment['name'],
                        $attachment['options'] ?? []
                    );
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Custom Headers
        |--------------------------------------------------------------------------
        |
        | Support custom email headers passed through the
        | message data array.
        |
        | Format:
        |
        | 'headers' => [
        |     'X-School-ID' => 'SCHOOL-123',
        |     'X-Message-ID' => 'MSG-456',
        | ]
        |
        */

        if (! empty($this->message->data['headers'])) {

            foreach ($this->message->data['headers'] as $key => $value) {

                $this->withSymfonyMessage(
                    function ($message) use ($key, $value): void {
                        $message->getHeaders()->addTextHeader(
                            $key,
                            $value
                        );
                    }
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Priority
        |--------------------------------------------------------------------------
        |
        | Map the Message priority string to the email priority level.
        |
        | Mapping:
        |
        | 'high'   => 1
        | 'normal' => 3
        | 'low'    => 5
        |
        */

        if ($this->message->priority !== null) {

            $priorityMap = [
                'high' => 1,
                'normal' => 3,
                'low' => 5,
            ];

            $level = $priorityMap[$this->message->priority]
                ?? 3;

            $this->withSymfonyMessage(
                function ($message) use ($level): void {
                    $message->getHeaders()->addTextHeader(
                        'X-Priority',
                        (string) $level
                    );
                }
            );
        }


        return $this;
    }
}
