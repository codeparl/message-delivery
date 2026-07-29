<?php

namespace Schoolpalm\MessageDelivery\Contracts;

interface TemplateRenderer
{
    public function render(string $template, array $data = []): string;
}
