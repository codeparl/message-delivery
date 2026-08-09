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
            $key = trim((string) $key);

            if (is_array($value)) {
                $value = $value['name']
                    ?? $value['title']
                    ?? json_encode(
                        $value,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    );
            } elseif (is_object($value)) {
                $value = method_exists($value, '__toString')
                    ? (string) $value
                    : ($value->name ?? $value->title ?? get_class($value));
            }

            $replacement = (string) ($value ?? '');

            /*
             * Match:
             *
             * {{name}}
             * {{ name }}
             * {{  name  }}
             * {name}
             * { name }
             * {  name  }
             */
            $content = preg_replace(
                '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}|\{\s*' . preg_quote($key, '/') . '\s*\}/u',
                $replacement,
                $content
            ) ?? $content;
        }

        return $content;
    }

    /**
     * Extract variables used in template.
     */
    public function extract(string $content): array
    {
        preg_match_all(
            '/\{\{?\s*([a-zA-Z0-9_.-]+)\s*\}\}?/u',
            $content,
            $matches
        );

        return array_values(array_unique(
            array_map(
                static fn(string $key): string => trim($key),
                $matches[1] ?? []
            )
        ));
    }
}
