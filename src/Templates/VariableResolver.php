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

            // Prevent "Array to string conversion" errors if a variable is an array or object
            if (is_array($value)) {
                $value = $value['name'] ?? $value['title'] ?? json_encode($value);
            } elseif (is_object($value)) {
                $value = method_exists($value, '__toString')
                    ? (string) $value
                    : ($value->name ?? $value->title ?? get_class($value));
            }

            $content = str_replace(
                [
                    '{{ ' . $key . ' }}',
                    '{{' . $key . '}}',
                    '{ ' . $key . ' }',
                    '{' . $key . '}',
                ],
                (string) ($value ?? ''),
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
     * "Hello {{name}} or {name}"
     *
     * returns:
     *
     * ['name', 'name']
     */
    public function extract(
        string $content
    ): array {

        preg_match_all(
            '/(?:{{\s*(.*?)\s*}}|{\s*(.*?)\s*})/',
            $content,
            $matches
        );


        return array_values(array_filter(array_merge($matches[1] ?? [], $matches[2] ?? [])));
    }
}
