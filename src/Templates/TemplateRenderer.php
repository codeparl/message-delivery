<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Templates;

use SchoolPalm\MessageDelivery\Contracts\TemplateRenderer as TemplateRendererContract;

// 1. Implement the contract so this class can be used interchangeably
final class TemplateRenderer implements TemplateRendererContract
{
    public function __construct(
        protected TemplateRendererContract $engine
    ) {}

    /**
     * Render template.
     */
    public function render(
        string $template,
        array $data = []
    ): string {
        return $this->engine->render(
            $template,
            $data
        );
    }

    /**
     * Determine if template exists.
     */
    public function exists(
        string $template
    ): bool {
        return $this->engine->exists(
            $template
        );
    }
}
