<?php

declare(strict_types=1);

namespace SchoolPalm\MessageDelivery\Templates;

final class VariableResolver
{
    /**
     * Resolve variables in template content.
     */
    public function resolve(
        string $content,
        array $variables = []
    ): string {

        foreach ($variables as $key => $value) {

            $content = str_replace(
                [
                    '{{ ' . $key . ' }}',
                    '{{' . $key . '}}',
                ],
                (string) $value,
                $content
            );
        }


        return $content;
    }


    /**
     * Extract variables used in template.
     *
     * Example:
     *
     * "Hello {{name}}"
     *
     * returns:
     *
     * ['name']
     */
    public function extract(
        string $content
    ): array {

        preg_match_all(
            '/{{\s*(.*?)\s*}}/',
            $content,
            $matches
        );


        return $matches[1] ?? [];
    }
}
