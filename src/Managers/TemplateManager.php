<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Managers;

use InvalidArgumentException;
use SchoolPalm\MessageDelivery\Templates\Template;

final class TemplateManager
{
    /**
     * Registered templates.
     *
     * This will later be replaced/extended
     * with database repository support.
     *
     * @var array<string, Template>
     */
    protected array $templates = [];


    /**
     * Register template.
     */
    public function register(
        Template $template
    ): void {

        $this->templates[$template->name] = $template;
    }


    /**
     * Find template.
     *
     * @throws InvalidArgumentException
     */
    public function find(
        string $name
    ): Template {

        if (! isset($this->templates[$name])) {

            throw new InvalidArgumentException(
                "Message template [{$name}] was not found."
            );
        }


        return $this->templates[$name];
    }


    /**
     * Check if template exists.
     */
    public function exists(
        string $name
    ): bool {

        return isset(
            $this->templates[$name]
        );
    }


    /**
     * Render a template.
     */
    public function render(
        string $name,
        array $data = []
    ): string {

        return $this->find($name)
            ->render($data);
    }


    /**
     * Get all registered templates.
     *
     * @return array<string, Template>
     */
    public function all(): array
    {
        return $this->templates;
    }


    /**
     * Remove template.
     */
    public function forget(
        string $name
    ): void {

        unset(
            $this->templates[$name]
        );
    }
}
