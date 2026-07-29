<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Contracts;

interface TemplateRenderer
{
    /**
     * Render a template.
     *
     * @param string $template
     * @param array $data
     */
    public function render(
        string $template,
        array $data = []
    ): string;


    /**
     * Check if template exists.
     */
    public function exists(
        string $template
    ): bool;
}
