<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Templates;

final class Template
{
    /**
     * Create template.
     */
    public function __construct(
        public readonly string $name,

        public readonly string $channel,

        public readonly string $content,

        public readonly array $variables = [],

        public readonly ?string $subject = null,
    ) {}


    /**
     * Check if template has subject.
     *
     * Mainly useful for email templates.
     */
    public function hasSubject(): bool
    {
        return $this->subject !== null;
    }


    /**
     * Check if template contains variables.
     */
    public function hasVariables(): bool
    {
        return ! empty($this->variables);
    }


    /**
     * Render template variables.
     */
    public function render(
        array $data = []
    ): string {

        $resolver = new VariableResolver();

        return $resolver->resolve(
            $this->content,
            $data
        );
    }


    /**
     * Convert template to array.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'channel' => $this->channel,
            'content' => $this->content,
            'variables' => $this->variables,
            'subject' => $this->subject,
        ];
    }
}
